<?php

namespace Tagcade\Service;

use DateInterval;
use DatePeriod;
use DateTime;
use DirectoryIterator;
use Exception;
use Facebook\WebDriver\Exception\NoSuchElementException;
use Facebook\WebDriver\Exception\NoSuchWindowException;
use Facebook\WebDriver\Exception\ScriptTimeoutException;
use Facebook\WebDriver\Exception\TimeOutException;
use Facebook\WebDriver\Exception\WebDriverCurlException;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverDimension;
use Facebook\WebDriver\WebDriverPoint;
use Psr\Log\LoggerInterface;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Symfony\Component\Filesystem\Filesystem;
use Tagcade\Exception\LoginFailException;
use Tagcade\Exception\RuntimeException;
use Tagcade\Service\Core\TagcadeRestClientInterface;
use Tagcade\Service\Fetcher\Params\PartnerParamInterface;
use Tagcade\Service\Fetcher\PartnerFetcherInterface;
use Tagcade\WebDriverFactoryInterface;

class WebDriverService implements WebDriverServiceInterface
{
    /** @var LoggerInterface */
    protected $logger;

    /** @var WebDriverFactoryInterface */
    protected $webDriverFactory;

    /** @var string */
    protected $symfonyAppDir;

    /** @var string */
    protected $defaultDataPath;

    /** @var string */
    protected $integrationCName;

    /** @var TagcadeRestClientInterface */
    protected $tagcadeRestClient;

    /** @var  string */
    private $chromeFolderPath;

    /** @var DeleteFileService */
    private $deleteFileService;

    /** @var TagcadeRestClientInterface */
    protected $restClient;

    /**
     * @param LoggerInterface $logger
     * @param WebDriverFactoryInterface $webDriverFactory
     * @param TagcadeRestClientInterface $tagcadeRestClient
     * @param string $symfonyAppDir
     * @param string $defaultDataPath
     * @param $chromeFolderPath
     * @param DeleteFileService $deleteFileService
     * @param TagcadeRestClientInterface $restClient
     */
    public function __construct(LoggerInterface $logger, WebDriverFactoryInterface $webDriverFactory, TagcadeRestClientInterface $tagcadeRestClient, $symfonyAppDir, $defaultDataPath, $chromeFolderPath, DeleteFileService $deleteFileService, TagcadeRestClientInterface $restClient)
    {
        $this->logger = $logger;
        $this->webDriverFactory = $webDriverFactory;
        $this->tagcadeRestClient = $tagcadeRestClient;
        $this->symfonyAppDir = $symfonyAppDir;
        $this->defaultDataPath = $defaultDataPath;
        $this->chromeFolderPath = $chromeFolderPath;
        $this->deleteFileService = $deleteFileService;
        $this->restClient = $restClient;
    }

    /**
     * @inheritdoc
     */
    public function doGetData(PartnerFetcherInterface $partnerFetcher, PartnerParamInterface $partnerParams)
    {
        $driver = null;
        $rootDownloadDir = $this->defaultDataPath;
        $isRelativeToProjectRootDir = (strpos($rootDownloadDir, './') === 0 || strpos($rootDownloadDir, '/') !== 0);
        $rootDownloadDir = $isRelativeToProjectRootDir ? sprintf('%s/%s', rtrim($this->symfonyAppDir, '/app'), ltrim($rootDownloadDir, './')) : $rootDownloadDir;
        if (!is_writable($rootDownloadDir)) {
            $this->logger->debug(sprintf('Cannot write to data-path %s', $rootDownloadDir));
            return 1;
        }

        // dataFolder: store data (report file, metadata file) that is for to Directory monitor running
        $dataFolder = WebDriverService::getDownloadPath(
            $rootDownloadDir,
            $partnerParams->getPublisherId(),
            $partnerParams->getIntegrationCName(),
            new \DateTime(),
            $partnerParams->getStartDate(),
            $partnerParams->getEndDate(),
            $partnerParams->getProcessId()
        );

        // defaultDownloadPath: temporarily store download report files, that is for fetcher running
        $defaultDownloadPath = sprintf('%s-%s', $dataFolder, 'dl');

        $this->logger->info(sprintf('Creating web driver with identifier param %s', $defaultDownloadPath));
        $webDriverConfig = [
            'publisher_id' => $partnerParams->getPublisherId(),
            'partner_cname' => $partnerParams->getIntegrationCName(),
            'force-new-session' => array_key_exists('force-new-session', $partnerParams->getConfig()) ? $partnerParams->getConfig()['force-new-session'] : true,
            'quit-web-driver-after-run' => array_key_exists('quit-web-driver-after-run', $partnerParams->getConfig()) ? $partnerParams->getConfig()['quit-web-driver-after-run'] : true,
            'process_id' => $partnerParams->getProcessId()
        ];

        $this->webDriverFactory->setConfig($webDriverConfig);
        $this->webDriverFactory->setParams($partnerParams);
        $driver = $this->webDriverFactory->getWebDriver($defaultDownloadPath);

        if (!$driver) {
            $this->logger->critical('Failed to create web driver from session');
            return 1;
        }

        $newConfig = $partnerParams->getConfig();
        $newConfig['defaultDownloadPath'] = $defaultDownloadPath;
        $partnerParams->setConfig($newConfig);

        // do get report breakdown by day if has
        if ($partnerParams->isDailyBreakdown()) {
            $startDate = clone $partnerParams->getStartDate();
            $endDate = clone $partnerParams->getEndDate();
            $endDate = $endDate->modify('+1 day'); // add 1 day for DateInterval correctly loop from startDate to endDate

            $interval = new DateInterval('P1D');
            $dateRange = new DatePeriod($startDate, $interval, $endDate);

            foreach ($dateRange as $i => $singleDate) {
                // override startDate/endDate by singleDate
                /** @var DateTime $singleDate */
                $partnerParamsWithSingleDate = clone $partnerParams;
                $partnerParamsWithSingleDate->setStartDate($singleDate);
                $partnerParamsWithSingleDate->setEndDate($singleDate);

                $newConfig = $partnerParamsWithSingleDate->getConfig();
                // append subDir to make sure not conflict path with other files
                // this keep file and metadata file are in pair
                $dataFolderForSingleDate = sprintf('%s/%s-%s', $dataFolder, $singleDate->format('Ymd'), (new DateTime())->getTimestamp());

                $newConfig['force-new-session'] = true; // always new session for the new config download path
                $newConfig['quit-web-driver-after-run'] = $singleDate == $partnerParams->getEndDate(); // quit web-driver if reach endDate

                $newConfig['startDate'] = $singleDate->format('Y-m-d');
                $newConfig['endDate'] = $singleDate->format('Y-m-d');
                $newConfig['defaultDownloadPath'] = $defaultDownloadPath;

                $partnerParamsWithSingleDate->setConfig($newConfig);

                $needToLogin = $i == 0 ? true : false;
                $this->getDataForPublisher($driver, $partnerFetcher, $partnerParamsWithSingleDate, $defaultDownloadPath, $dataFolderForSingleDate, $needToLogin);
            }
        } else {
            /**
             *  Get report by monthly breakdown
             *  Avoid large download files because dateRange is so far
             *
             *  If startDate and endDate in the same month, we get 1 report for this
             *  If startDate and endDate in next month, example 2017-02-14 and 2017-13-10, we get 2 reports for 2 months
             *  For dateRanges from 2016-10-11 to 2017-03-20, we have dateRanges as
             *  [
             *      2016-10-11 => 2016-10-31,
             *      2016-11-01 => 2016-11-30,
             *      2016-12-01 => 2017-12-31,
             *      2017-01-01 => 2017-01-31,
             *      2017-02-01 => 2017-02-28,
             *      2017-03-01 => 2017-03-20,
             *  ]
             */
            $startDate = clone $partnerParams->getStartDate();
            $endDate = clone $partnerParams->getEndDate();

            $dateRanges = [];
            while ($startDate <= $endDate) {
                $temp = $startDate->format('Y-m-d');
                $startDate->modify('last day of this month');
                $dateRanges[$temp] = $startDate < $endDate ? $startDate->format('Y-m-d') : $endDate->format('Y-m-d');
                $startDate->modify('first day of next month');
            }

            $needToLogin = true;
            foreach ($dateRanges as $monthStartDate => $monthEndDate) {
                $partnerParamsForMonth = clone $partnerParams;
                $partnerParamsForMonth->setStartDate(date_create($monthStartDate));
                $partnerParamsForMonth->setEndDate(date_create($monthEndDate));

                $dataFolderForMonth = sprintf('%s/%s-%s', $dataFolder, $monthStartDate, (new DateTime())->getTimestamp());
                $this->getDataForPublisher($driver, $partnerFetcher, $partnerParamsForMonth, $defaultDownloadPath, $dataFolderForMonth, $needToLogin);

                $needToLogin = false;
            }
        }

        //remove default download directory
        $this->logger->info('Remove default download directory');
        $this->deleteFileService->removeFileOrFolder($defaultDownloadPath);

        if ($driver instanceof RemoteWebDriver) {
            $partnerFetcher->doLogout($partnerParams, $driver);
            $driver->quit();
        }

        $this->removeSessionFolders();

        $this->restClient->updateIntegrationWhenDownloadSuccess($partnerParams);

        return 1;
    }

    /**
     * @param string $rootDir
     * @param string $publisherId
     * @param string $integrationCName
     * @param DateTime $executionDate
     * @param DateTime $startDate
     * @param DateTime $endDate
     * @param int $processId
     * @param null|string $subDir
     * @return string
     */
    public static function getDownloadPath($rootDir, $publisherId, $integrationCName, DateTime $executionDate, DateTime $startDate, DateTime $endDate, $processId, $subDir = null)
    {
        $downloadPath = sprintf(
            '%s/%d/%s/%s-%s-%s-%s',
            $rootDir,
            $publisherId,
            $integrationCName,
            $executionDate->format('Ymd'),
            $startDate->format('Ymd'),
            $endDate->format('Ymd'),
            $processId
        );

        // append subDir if has
        if (!empty($subDir)) {
            $downloadPath = sprintf('%s/%s', $downloadPath, $subDir);
        }

        return $downloadPath;
    }

    /**
     * get Data For Publisher
     *
     * @param RemoteWebDriver $driver
     * @param PartnerFetcherInterface $partnerFetcher
     * @param PartnerParamInterface $params
     * @param null|string $dataFolder the dataFolder (contains metadata and report file)
     * @param bool $needToLogin
     * @param $downloadFolder
     * @return bool
     * @throws Exception
     * @throws LoginFailException
     */
    protected function getDataForPublisher(RemoteWebDriver $driver, PartnerFetcherInterface $partnerFetcher, PartnerParamInterface $params, $downloadFolder, $dataFolder, $needToLogin = true)
    {
        if (!is_dir($downloadFolder)) {
            mkdir($downloadFolder, 0755, true);
        }

        if (!is_dir($dataFolder)) {
            mkdir($dataFolder, 0755, true);
        }

        // create lock file in download folder
        $lockFilePathForDownloadFolder = $this->createLockFile($downloadFolder);
        if (!$lockFilePathForDownloadFolder) {
            // re-throw for retry handle
            throw new RuntimeException(sprintf('Could not create lock file in download folder %s', $downloadFolder));
        }

        // create lock file in data folder
        $lockFilePath = $this->createLockFile($dataFolder);
        if (!$lockFilePath) {
            // re-throw for retry handle
            throw new RuntimeException(sprintf('Could not create lock file in data folder %s', $dataFolder));
        }

        // set webDriver config
        $webDriverConfig = [
            'publisher_id' => $params->getPublisherId(),
            'partner_cname' => $params->getIntegrationCName(),
            'force-new-session' => array_key_exists('force-new-session', $params->getConfig()) ? $params->getConfig()['force-new-session'] : true,
            'quit-web-driver-after-run' => array_key_exists('quit-web-driver-after-run', $params->getConfig()) ? $params->getConfig()['quit-web-driver-after-run'] : true,
            'process_id' => $params->getProcessId()
        ];

        $this->webDriverFactory->setConfig($webDriverConfig);
        $this->webDriverFactory->setParams($params);

        try {
            $driver->manage()
                ->timeouts()
                ->implicitlyWait(4)
                ->pageLoadTimeout(20);

            $driver->manage()->window()->setPosition(new WebDriverPoint(0, 0));
            // todo make this configurable
            $driver->manage()->window()->setSize(new WebDriverDimension(1920, 1080));

            $this->logger->info('Fetcher starts to get data');

            $this->handleGetDataByDateRange($partnerFetcher, $params, $driver, $needToLogin);

            $this->logger->debug(sprintf('Finished getting %s data', get_class($partnerFetcher)));
        } catch (LoginFailException $loginFailException) {
            // remove lock file in download folder
            $this->removeLockFile($lockFilePathForDownloadFolder, $downloadFolder);
            // remove lock file
            $this->removeLockFile($lockFilePath, $dataFolder);

            $this->tagcadeRestClient->createAlertWhenLoginFail(
                $loginFailException->getPublisherId(),
                $loginFailException->getIntegrationCName(),
                $loginFailException->getDataSourceId(),
                $loginFailException->getStartDate(),
                $loginFailException->getEndDate(),
                $loginFailException->getExecutionDate()
            );

            $driver->quit();

            // re-throw for retry handle
            throw $loginFailException;
        } catch (TimeOutException $timeoutException) {
            // remove lock file in download folder
            $this->removeLockFile($lockFilePathForDownloadFolder, $downloadFolder);
            // remove lock file
            $this->removeLockFile($lockFilePath, $dataFolder);

            $this->tagcadeRestClient->createAlertWhenTimeOut(
                $params->getPublisherId(),
                $params->getIntegrationCName(),
                $params->getDataSourceId(),
                $params->getStartDate(),
                $params->getEndDate(),
                date("Y-m-d H:i:s")
            );

            $driver->quit();

            // any timeout (by wait util...) is retryable
            // re-throw for retry handle
            throw new RuntimeException($timeoutException->getMessage());
        } catch (ScriptTimeoutException $timeoutException) {
            // remove lock file in download folder
            $this->removeLockFile($lockFilePathForDownloadFolder, $downloadFolder);
            // remove lock file
            $this->removeLockFile($lockFilePath, $dataFolder);

            $this->tagcadeRestClient->createAlertWhenTimeOut(
                $params->getPublisherId(),
                $params->getIntegrationCName(),
                $params->getDataSourceId(),
                $params->getStartDate(),
                $params->getEndDate(),
                date("Y-m-d H:i:s")
            );

            $driver->quit();

            // any timeout (by wait util...) is retryable
            // re-throw for retry handle
            throw new RuntimeException($timeoutException->getMessage());
        } catch (NoSuchElementException $noSuchElementException) {
            // remove lock file in download folder
            $this->removeLockFile($lockFilePathForDownloadFolder, $downloadFolder);
            // remove lock file
            $this->removeLockFile($lockFilePath, $dataFolder);

            // element may be not existed due to timeout or temporarily changed
            // this is retryable
            $driver->quit();

            throw new RuntimeException($noSuchElementException->getMessage());
        } catch (WebDriverCurlException $webDriverCurlException) {
            // remove lock file in download folder
            $this->removeLockFile($lockFilePathForDownloadFolder, $downloadFolder);
            // remove lock file
            $this->removeLockFile($lockFilePath, $dataFolder);

            $this->tagcadeRestClient->createAlertWhenTimeOut(
                $params->getPublisherId(),
                $params->getIntegrationCName(),
                $params->getDataSourceId(),
                $params->getStartDate(),
                $params->getEndDate(),
                date("Y-m-d H:i:s")
            );

            $driver->quit();

            // any timeout (by wait util...) is retryable
            // re-throw for retry handle
            throw new RuntimeException($webDriverCurlException->getMessage());
        } catch (NoSuchWindowException $noSuchWindowException) {
            // remove lock file in download folder
            $this->removeLockFile($lockFilePathForDownloadFolder, $downloadFolder);
            // remove lock file
            $this->removeLockFile($lockFilePath, $dataFolder);

            $this->tagcadeRestClient->createAlertWhenLoginFail(
                $params->getPublisherId(),
                $params->getIntegrationCName(),
                $params->getDataSourceId(),
                $params->getStartDate(),
                $params->getEndDate(),
                date("Y-m-d H:i:s")
            );

            $driver->quit();

            // any timeout (by wait util...) is retryable
            // re-throw for retry handle
            throw new RuntimeException($noSuchWindowException->getMessage());
        } catch (Exception $e) {
            // remove lock file in download folder
            $this->removeLockFile($lockFilePathForDownloadFolder, $downloadFolder);
            // remove lock file
            $this->removeLockFile($lockFilePath, $dataFolder);

            $message = $e->getMessage() ? $e->getMessage() : $e->getTraceAsString();
            $this->logger->critical($message);

            $driver->quit();

            // re-throw for retry handle
            throw $e;
        }

        // create metadata file in dataFolder
        $this->saveMetaDataFile($params, $dataFolder);

        // waiting for download complete in downloadFolder
        // TODO: use DownloadFileHelper wait download complete instead of here
        $this->waitDownloadComplete($downloadFolder);

        // add startDate endDate to Downloaded file name
        $this->addStartDateEndDateToDownloadFiles($downloadFolder, $params);

        // Move downloaded files to dataFolder
        $this->moveDownloadedFilesToDataFolder($downloadFolder, $dataFolder);

        // remove lock file. Now this data folder is ready for Directory monitor!
        //// remove lock file in download folder
        $this->removeLockFile($lockFilePathForDownloadFolder, $downloadFolder);
        //// remove lock file in data folder
        $this->removeLockFile($lockFilePath, $dataFolder);

        return true;
    }

    /**
     * handle Get Data By DateRange
     *
     * @param PartnerFetcherInterface $partnerFetcher
     * @param PartnerParamInterface $params
     * @param RemoteWebDriver $driver
     * @param $needToLogin
     */
    protected function handleGetDataByDateRange(PartnerFetcherInterface $partnerFetcher, PartnerParamInterface $params, RemoteWebDriver $driver, $needToLogin = false)
    {
        // step1. login
        $partnerFetcher->doLogin($params, $driver, $needToLogin);

        // small delay
        usleep(10);

        // step2. get all report data
        $partnerFetcher->getAllData($params, $driver);
    }

    /**
     * @param $directory
     * @return int
     */
    private function getDirSize($directory)
    {
        $size = 0;
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory)) as $file) {
            $size += $file->getSize();
        }
        return $size;
    }

    /**
     * Remove old session folders
     */
    private function removeSessionFolders()
    {
        $this->logger->debug(sprintf('Remove session folder', $this->chromeFolderPath));
        $iterator = new DirectoryIterator($this->chromeFolderPath);
        foreach ($iterator as $sessionFolder) {
            if ($sessionFolder->isDot()) {
                // ignore current directory '.' and parent directory '..'
                continue;
            }

            $folderPath = $sessionFolder->getRealPath();
            if (strpos($folderPath, $this->chromeFolderPath) !== 0) {
                // critical sanity check, folders must contain the chrome folder path
                continue;
            }

            $modifiedDate = new DateTime(date('Y/m/d', $sessionFolder->getMTime()));
            $today = new DateTime();

            $diff = $today->diff($modifiedDate);

            if ($diff->y || $diff->m || $diff->d > 1) {
                $this->deleteFileService->removeFileOrFolder($folderPath);
            }
        }
    }

    /**
     * @param $folder
     * @param PartnerParamInterface $param
     */
    private function addStartDateEndDateToDownloadFiles($folder, PartnerParamInterface $param)
    {
        $this->logger->debug(sprintf('Add startDate, endDate to download files', $folder));
        $subFiles = scandir($folder);

        $subFiles = array_map(function ($subFile) use ($folder) {
            return $folder . '/' . $subFile;
        }, $subFiles);

        $subFiles = array_filter($subFiles, function ($file) {
            return is_file($file) && (new \SplFileInfo($file))->getExtension() != 'lock';
        });

        $time = sprintf('DTS-%s-From-%s-To-%s', $param->getDataSourceId(), $param->getStartDate()->format('Y-m-d'), $param->getEndDate()->format('Y-m-d'));

        foreach ($subFiles as $subFile) {
            $subFile = new \SplFileInfo($subFile);
            if ($subFile->getExtension() != 'meta') {
                $path = $subFile->getRealPath();
                if (strpos($path, $time)) {
                    continue;
                }

                $dotPos = strpos($path, '.');
                if ($dotPos) {
                    $newName = sprintf("%s_%s%s",
                        substr($path, 0, $dotPos),
                        $time,
                        substr($path, $dotPos));
                    rename($subFile->getRealPath(), $newName);
                }
            }
        }
    }

    /**
     * @param PartnerParamInterface $params
     * @param string $dataFolder
     */
    private function saveMetaDataFile(PartnerParamInterface $params, $dataFolder)
    {
        $this->logger->debug(sprintf('Save meta data file', $dataFolder));
        // create metadata file
        $metadata = [
            'module' => 'integration',
            'publisherId' => $params->getPublisherId(),
            'dataSourceId' => $params->getDataSourceId(),
            'integrationCName' => $params->getIntegrationCName(),
            // 'date' => ...set later if single date...,
            'uuid' => bin2hex(random_bytes(15)) // make all metadata files have difference hash values when being processed in directory monitor
        ];

        //// date in metadata is only available if startDate equal endDate (day by day breakdown)
        if ($params->getStartDate() == $params->getEndDate()) {
            $metadata['date'] = $params->getStartDate()->format('Y-m-d');
        }

        // create metadata file
        $metadataFileName = sprintf('%s-%s-%s.%s',
            $params->getIntegrationCName(),
            $params->getStartDate()->format('Ymd'),
            $params->getEndDate()->format('Ymd'),
            'meta'
        );

        $metaDataFolder = $dataFolder;
        if (!is_dir($metaDataFolder)) {
            mkdir($metaDataFolder, 0755, true);
        }
        $metadataFilePath = sprintf('%s/%s', $metaDataFolder, $metadataFileName);
        file_put_contents($metadataFilePath, json_encode($metadata));
    }

    /**
     * move Downloaded Files To DataFolder, exclude .lock file
     *
     * @param $downloadFolder
     * @param $dataFolder
     */
    private function moveDownloadedFilesToDataFolder($downloadFolder, $dataFolder)
    {
        $this->logger->debug(sprintf('Move download files to data folder', $downloadFolder, $dataFolder));

        $subFiles = scandir($downloadFolder);

        $subFiles = array_filter($subFiles, function ($subFile) use ($downloadFolder) {
            return is_file($downloadFolder . '/' . $subFile) && (new \SplFileInfo($subFile))->getExtension() != 'lock';
        });

        array_walk($subFiles, function ($file) use ($downloadFolder, $dataFolder) {
            rename($downloadFolder . '/' . $file, $dataFolder . '/' . $file);
        });
    }

    /**
     * @param $downloadFolder
     */
    private function waitDownloadComplete($downloadFolder)
    {
        $this->logger->debug(sprintf('Wait download complete', $downloadFolder));
        // check that chrome finished downloading all files before finishing
        sleep(5);

        do {
            $fileSize1 = $this->getDirSize($downloadFolder);  // check file size
            sleep(5);
            $fileSize2 = $this->getDirSize($downloadFolder);
        } while ($fileSize1 != $fileSize2);

        sleep(3);
    }

    /**
     * create Lock File
     *
     * @param $dataFolder
     * @return string|false false if input invalid or cannot create file
     */
    private function createLockFile($dataFolder)
    {
        if (!is_string($dataFolder) || empty($dataFolder) || !is_writable($dataFolder)) {
            return false;
        }

        $lockFileName = sprintf('%s.lock', bin2hex(random_bytes(15)));
        $lockFilePath = sprintf('%s/%s', $dataFolder, $lockFileName);
        file_put_contents($lockFilePath, sprintf('This is lock file for data folder %s', $dataFolder));

        $this->logger->info(sprintf('Creating lock file', $lockFilePath, $dataFolder));

        return $lockFilePath;
    }

    /**
     * remove File Lock
     *
     * @param string $lockFilePath
     * @param string $dataFolder for logger only
     */
    private function removeLockFile($lockFilePath, $dataFolder)
    {
        $this->logger->info(sprintf('Removing lock file', $lockFilePath, $dataFolder));

        $fileSystem = new Filesystem();
        try {
            $fileSystem->remove($lockFilePath);
        } catch (Exception $e) {

        }
    }
}