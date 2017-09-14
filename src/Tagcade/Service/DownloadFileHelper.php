<?php

namespace Tagcade\Service;


use Exception;
use Facebook\WebDriver\Remote\RemoteWebElement;
use Psr\Log\LoggerInterface;
use Symfony\Component\Finder\SplFileInfo;

class DownloadFileHelper implements DownloadFileHelperInterface
{
    const RESCAN_TIME_IN_SECONDS = 5;
    const NO_PARTIAL_FILE_RESCAN_TIME_IN_SECONDS = 0.25;
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

    /* @var DeleteFileService  */
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
}