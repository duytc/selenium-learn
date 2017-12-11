<?php

namespace Tagcade\Service;


use Exception;
use Facebook\WebDriver\Remote\RemoteWebElement;
use Psr\Log\LoggerInterface;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Symfony\Component\Finder\SplFileInfo;
use Tagcade\Service\Fetcher\Params\PartnerParamInterface;

class DownloadFileHelper implements DownloadFileHelperInterface
{
    const RESCAN_TIME_IN_SECONDS = 5;
    const NO_PARTIAL_FILE_RESCAN_TIME_IN_SECONDS = 0.25;
    const XLS_CONTENT_TYPE = 'application/vnd.ms-excel';
    const XLSX_CONTENT_TYPE = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
    const XML_CONTENT_TYPE = 'application/xml';
    const JSON_CONTENT_TYPE = 'application/json';
    const CSV_CONTENT_TYPE = 'text/csv';

    /**
     * @var string
     */
    private $downloadRootDirectory;
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var
     */
    private $rootKernelDirectory;

    /* @var DeleteFileService */
    private $deleteFileService;

    function __construct($downloadRootDirectory, LoggerInterface $logger, $rootKernelDirectory, DeleteFileService $deleteFileService)
    {
        $this->downloadRootDirectory = sprintf('%s/', $downloadRootDirectory);
        $this->logger = $logger;
        $this->rootKernelDirectory = $rootKernelDirectory;
        $this->deleteFileService = $deleteFileService;
    }

    /**
     * @inheritdoc
     */
    public function deleteFilesByExtension($fileExtensions = array('crdownload'))
    {
        if (!file_exists($this->downloadRootDirectory)) {
            throw new Exception(sprintf('This folder system %s does not exist', $this->downloadRootDirectory));
        }

        if (!is_dir($this->downloadRootDirectory)) {
            throw new Exception('This path is not directory');
        }

        if (!is_array($fileExtensions)) {
            throw new Exception(sprintf('File extension is not a array. This value is %s', $fileExtensions));
        }

        $files = $this->getPartialDownloadFiles($fileExtensions);
        foreach ($files as $file) {
            $this->deleteFileService->removeFileOrFolder($file);
        }
    }

    /**
     *
     * Count files by extension
     *
     * @param array $fileExtensions
     * @return int
     * @throws \Exception
     */
    public function countFilesByExtension($fileExtensions = array('crdownload'))
    {
        if (!file_exists($this->downloadRootDirectory)) {
            throw new Exception(sprintf('This folder system %s does not exist', $this->downloadRootDirectory));
        }

        if (!is_dir($this->downloadRootDirectory)) {
            throw new Exception('This path is not directory');
        }

        if (!is_array($fileExtensions)) {
            throw new Exception(sprintf('File extension is not a array. This value is %s', $fileExtensions));
        }

        $files = $this->getPartialDownloadFiles($fileExtensions);

        return count($files);
    }

    /**
     * @inheritdoc
     */
    public function downloadThenWaitUntilComplete(RemoteWebElement $clickAbleElement, $directoryStoreDownloadFile)
    {
        if (!$clickAbleElement instanceof RemoteWebElement) {
            $this->logger->debug("Invalid remove web element");
            return $this;
        }

        if (!is_dir($directoryStoreDownloadFile)) {
            $this->logger->debug(sprintf('Path to store data download is not directory, %s', $directoryStoreDownloadFile));
            return $this;
        }

        $this->logger->debug('Click to download element');
        $clickAbleElement->click();

        // TODO: do wait for download complete here instead of in WebDriverService

        return $this;
    }

    /**
     * @param $downloadDirectory
     * @param array $fileExtensions
     * @return array
     */
    private function getPartialDownloadFiles($downloadDirectory, $fileExtensions = array('crdownload'))
    {
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($downloadDirectory, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        $expectFiles = [];
        /** @var  SplFileInfo $fileInfo */
        foreach ($files as $fileInfo) {
            if (!in_array($fileInfo->getExtension(), $fileExtensions)) {
                continue;
            }

            $expectFiles[] = $fileInfo->getRealPath();
        }

        return $expectFiles;
    }

    /**
     * @return string
     */
    public function getRootDirectory()
    {
        $dataPath = $this->downloadRootDirectory;
        $isRelativeToProjectRootDir = (strpos($dataPath, './') === 0 || strpos($dataPath, '/') !== 0);
        $dataPath = $isRelativeToProjectRootDir ? sprintf('%s/%s', rtrim($this->rootKernelDirectory, '/app'), ltrim($dataPath, './')) : $dataPath;

        return $dataPath;
    }

    /**
     * @param $downloadDirectory
     * @return array
     * @throws \Exception
     */
    public function getAllFilesInDirectory($downloadDirectory)
    {
        if (!is_dir($downloadDirectory)) {
            throw new Exception(sprintf('This path is not directory, path is %s', $downloadDirectory));
        }

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($downloadDirectory, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        $expectFiles = [];
        /** @var  SplFileInfo $fileInfo */
        foreach ($files as $fileInfo) {
            $expectFiles[] = $fileInfo->getRealPath();
        }

        return $expectFiles;
    }

    /**
     * @param PartnerParamInterface $params
     * @param string $dataFolder
     */
    public function saveMetaDataFile(PartnerParamInterface $params, $dataFolder)
    {
        $this->logger->debug(sprintf('Save meta data file', $dataFolder));
        $uuid = bin2hex(random_bytes(2));
        // create metadata file
        $metadata = [
            'module' => 'integration',
            'publisherId' => $params->getPublisherId(),
            'dataSourceId' => $params->getDataSourceId(),
            'integrationCName' => $params->getIntegrationCName(),
            // 'date' => ...set later if single date...,
            'uuid' => $uuid // make all metadata files have difference hash values when being processed in directory monitor
        ];

        //// date in metadata is only available if startDate equal endDate (day by day breakdown)
        if ($params->getStartDate() == $params->getEndDate()) {
            $metadata['date'] = $params->getStartDate()->format('Y-m-d');
        }

        // create metadata file
        $metadataFileName = sprintf('%s-%s-%s-%s-%s.%s',
            $params->getIntegrationCName(),
            $params->getDataSourceId(),
            $params->getStartDate() instanceof \DateTime ? $params->getStartDate()->format('Ymd') : "",
            $params->getEndDate() instanceof \DateTime ? $params->getEndDate()->format('Ymd') : "",
            $uuid,
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
     * @param string $contentType
     * @return string
     */
    public function getFileExtension($contentType)
    {
        // todo, this could be in a service for reuse
        $contentType = preg_replace('/;.*/', '', $contentType);

        switch ($contentType) {
            case self::XLSX_CONTENT_TYPE:
                $fileType = '.xlsx';
                break;
            case self::XLS_CONTENT_TYPE:
                $fileType = '.xls';
                break;
            case self::XML_CONTENT_TYPE:
                $fileType = '.xml';
                break;
            case self::JSON_CONTENT_TYPE:
                $fileType = '.json';
                break;
            case self::CSV_CONTENT_TYPE:
                $fileType = '.csv';
                break;
            default:
                $fileType = '.txt';
        }

        return $fileType;
    }

    /**
     * @param $downloadFolder
     */
    public function waitDownloadComplete($downloadFolder)
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
     * @param $folder
     * @param PartnerParamInterface $param
     */
    public function addStartDateEndDateToDownloadFiles($folder, PartnerParamInterface $param)
    {
        $this->logger->debug(sprintf('Add startDate, endDate to download files', $folder));
        $subFiles = scandir($folder);

        $subFiles = array_map(function ($subFile) use ($folder) {
            return $folder . '/' . $subFile;
        }, $subFiles);

        $subFiles = array_filter($subFiles, function ($file) {
            return is_file($file) && (new \SplFileInfo($file))->getExtension() != 'lock';
        });

        $time = sprintf('DTS-%s-%s-%s-%s', $param->getDataSourceId(), $param->getStartDate()->format('Ymd'), $param->getEndDate()->format('Ymd'), bin2hex(random_bytes(2)));

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
     * move Downloaded Files To DataFolder, exclude .lock file
     *
     * @param $downloadFolder
     * @param $dataFolder
     */
    public function moveDownloadedFilesToDataFolder($downloadFolder, $dataFolder)
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
}