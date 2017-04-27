<?php

namespace Tagcade\Service;

use DateInterval;
use DatePeriod;
use DateTime;
use Exception;
use Facebook\WebDriver\Exception\NoSuchElementException;
use Facebook\WebDriver\Exception\TimeOutException;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverDimension;
use Facebook\WebDriver\WebDriverPoint;
use Psr\Log\LoggerInterface;
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

    /**
     * @param LoggerInterface $logger
     * @param WebDriverFactoryInterface $webDriverFactory
     * @param TagcadeRestClientInterface $tagcadeRestClient
     * @param string $symfonyAppDir
     * @param string $defaultDataPath
     */
    public function __construct(LoggerInterface $logger, WebDriverFactoryInterface $webDriverFactory, TagcadeRestClientInterface $tagcadeRestClient, $symfonyAppDir, $defaultDataPath)
    {
        $this->logger = $logger;
        $this->webDriverFactory = $webDriverFactory;
        $this->tagcadeRestClient = $tagcadeRestClient;
        $this->symfonyAppDir = $symfonyAppDir;
        $this->defaultDataPath = $defaultDataPath;
    }

    /**
     * @inheritdoc
     */
    public function doGetData(PartnerFetcherInterface $partnerFetcher, PartnerParamInterface $partnerParams)
    {
        $rootDownloadDir = $this->defaultDataPath;
        $isRelativeToProjectRootDir = (strpos($rootDownloadDir, './') === 0 || strpos($rootDownloadDir, '/') !== 0);
        $rootDownloadDir = $isRelativeToProjectRootDir ? sprintf('%s/%s', rtrim($this->symfonyAppDir, '/app'), ltrim($rootDownloadDir, './')) : $rootDownloadDir;
        if (!is_writable($rootDownloadDir)) {
            $this->logger->error(sprintf('Cannot write to data-path %s', $rootDownloadDir));
            return 1;
        }

        // do get report breakdown by day if has
        if ($partnerParams->isDailyBreakdown()) {
            $startDate = clone $partnerParams->getStartDate();
            $endDate = clone $partnerParams->getEndDate();
            $endDate = $endDate->modify('+1 day'); // add 1 day for DateInterval correctly loop from startDate to endDate

            $interval = new DateInterval('P1D');
            $dateRange = new DatePeriod($startDate, $interval, $endDate);

            foreach ($dateRange as $singleDate) {
                // override startDate/endDate by singleDate
                /** @var DateTime $singleDate */
                $partnerParamsWithSingleDate = clone $partnerParams;
                $partnerParamsWithSingleDate->setStartDate($singleDate);
                $partnerParamsWithSingleDate->setEndDate($singleDate);

                $newConfig = $partnerParamsWithSingleDate->getConfig();
                // append subDir to make sure not conflict path with other files
                // this keep file and metadata file are in pair
                $subDir = (new DateTime())->getTimestamp();
                $newConfig['subDir'] = $subDir;

                $newConfig['force-new-session'] = true; // always new session for the new config download path
                $newConfig['quit-web-driver-after-run'] = $singleDate == $partnerParams->getEndDate(); // quit web-driver if reach endDate

                $newConfig['startDate'] = $singleDate->format('Y-m-d');
                $newConfig['endDate'] = $singleDate->format('Y-m-d');

                $partnerParamsWithSingleDate->setConfig($newConfig);

                $this->getDataForPublisher($partnerFetcher, $partnerParamsWithSingleDate, $rootDownloadDir, $subDir);
            }
        } else {
            // do get report by full date range
            $this->getDataForPublisher($partnerFetcher, $partnerParams, $rootDownloadDir);
        }

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
     * @param PartnerFetcherInterface $partnerFetcher
     * @param PartnerParamInterface $params
     * @param string $rootDownloadDir
     * @param null|string $subDir the sub dir (last dir) before the file. This is for metadata comprehension mechanism
     * @return int
     * @throws Exception
     * @throws LoginFailException
     */
    protected function getDataForPublisher(PartnerFetcherInterface $partnerFetcher, PartnerParamInterface $params, $rootDownloadDir, $subDir = null)
    {
        $webDriverConfig = [
            'publisher_id' => $params->getPublisherId(),
            'partner_cname' => $params->getIntegrationCName(),
            'force-new-session' => array_key_exists('force-new-session', $params->getConfig()) ? $params->getConfig()['force-new-session'] : true,
            'quit-web-driver-after-run' => array_key_exists('quit-web-driver-after-run', $params->getConfig()) ? $params->getConfig()['quit-web-driver-after-run'] : true,
            'process_id' => $params->getProcessId()
        ];

        $this->webDriverFactory->setConfig($webDriverConfig);
        $this->webDriverFactory->setParams($params);

        $forceNewSession = $webDriverConfig['force-new-session'];
        $sessionId = null;

        if ($forceNewSession == false) {
            $sessionId = $this->webDriverFactory->getLastSessionId();
        } else {
            $this->webDriverFactory->clearAllSessions();
        }

        $identifier = $sessionId != null ? $sessionId : $rootDownloadDir;

        $this->logger->info(sprintf('Creating web driver with identifier param %s', $identifier));
        $driver = $this->webDriverFactory->getWebDriver($identifier, $rootDownloadDir, $subDir);
        // you could clear cache and cookies here if using the same profile

        if (!$driver) {
            $this->logger->critical('Failed to create web driver from session');
            return 1;
        }

        try {
            $driver->manage()
                ->timeouts()
                ->implicitlyWait(3)
                ->pageLoadTimeout(10);

            $driver->manage()->window()->setPosition(new WebDriverPoint(0, 0));
            // todo make this configurable
            $driver->manage()->window()->setSize(new WebDriverDimension(1920, 1080));

            $this->logger->info('Fetcher starts to get data');

            $this->handleGetDataByDateRange($partnerFetcher, $params, $driver);

            $this->logger->info(sprintf('Finished getting %s data', get_class($partnerFetcher)));
        } catch (LoginFailException $loginFailException) {
            $this->tagcadeRestClient->createAlertWhenLoginFail(
                $loginFailException->getPublisherId(),
                $loginFailException->getIntegrationCName(),
                $loginFailException->getDataSourceId(),
                $loginFailException->getStartDate(),
                $loginFailException->getEndDate(),
                $loginFailException->getExecutionDate()
            );

            // todo check that chrome finished downloading all files before finishing
            $quitWebDriverAfterRun = $webDriverConfig['quit-web-driver-after-run'];
            if ($quitWebDriverAfterRun) {
                $driver->quit();
            }

            // re-throw for retry handle
            throw $loginFailException;
        } catch (TimeOutException $timeoutException) {
            $this->tagcadeRestClient->createAlertWhenTimeOut(
                $params->getPublisherId(),
                $params->getIntegrationCName(),
                $params->getDataSourceId(),
                $params->getStartDate(),
                $params->getEndDate(),
                date("Y-m-d H:i:s")
            );

            // todo check that chrome finished downloading all files before finishing
            $quitWebDriverAfterRun = $webDriverConfig['quit-web-driver-after-run'];
            if ($quitWebDriverAfterRun) {
                $driver->quit();
            }

            // any timeout (by wait util...) is retryable
            // re-throw for retry handle
            throw new RuntimeException($timeoutException->getMessage());
        } catch (NoSuchElementException $noSuchElementException) {
            // element may be not existed due to timeout or temporarily changed
            // this is retryable
            throw new RuntimeException($noSuchElementException->getMessage());
        } catch (Exception $e) {
            $message = $e->getMessage() ? $e->getMessage() : $e->getTraceAsString();
            $this->logger->critical($message);

            // todo check that chrome finished downloading all files before finishing
            $quitWebDriverAfterRun = $webDriverConfig['quit-web-driver-after-run'];
            if ($quitWebDriverAfterRun) {
                $driver->quit();
            }

            // re-throw for retry handle
            throw $e;
        }

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

        $downloadPath = WebDriverService::getDownloadPath(
            $rootDownloadDir,
            $params->getPublisherId(),
            $params->getIntegrationCName(),
            $executionDate = new \DateTime(),
            $params->getStartDate(),
            $params->getEndDate(),
            $params->getProcessId(),
            $subDir
        );

        $metadataFilePath = sprintf('%s/%s', $downloadPath, $metadataFileName);
        file_put_contents($metadataFilePath, json_encode($metadata));

        // todo check that chrome finished downloading all files before finishing
        $quitWebDriverAfterRun = $webDriverConfig['quit-web-driver-after-run'];
        if ($quitWebDriverAfterRun) {
            $driver->quit();
        }

        return 0;
    }

    /**
     * handle Get Data By DateRange
     *
     * @param PartnerFetcherInterface $partnerFetcher
     * @param PartnerParamInterface $params
     * @param RemoteWebDriver $driver
     */
    protected function handleGetDataByDateRange(PartnerFetcherInterface $partnerFetcher, PartnerParamInterface $params, RemoteWebDriver $driver)
    {
        // step1. login
        $partnerFetcher->doLogin($params, $driver);

        // small delay
        usleep(10);

        // step2. get all report data
        $partnerFetcher->getAllData($params, $driver);
    }
}