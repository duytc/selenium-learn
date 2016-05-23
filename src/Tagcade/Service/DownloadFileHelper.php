<?php

namespace Tagcade\Service;


use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Remote\RemoteWebElement;
use Facebook\WebDriver\WebDriverBy;
use Psr\Log\LoggerInterface;
use Symfony\Component\Finder\SplFileInfo;

class DownloadFileHelper implements DownloadFileHelperInterface  {

    const  RESCAN_TIMEOUT = 5;
    /**
     * @var string
     */
    private $downloadRootDirectory;
    /**
     * @var int
     */
    private $downloadTimeout;
    /**
     * @var LoggerInterface
     */
    private $logger;

    function __construct($downloadRootDirectory, $downloadTimeout, LoggerInterface $logger)
    {
        $this->downloadRootDirectory = sprintf('%s/publishers', $downloadRootDirectory);
        $this->downloadTimeout = $downloadTimeout;
        $this->logger = $logger;
    }

    /**
     * @inheritdoc
     */
    public function deleteFilesByExtension($fileExtensions = array ('crdownload'))
    {
        if ( !file_exists($this->downloadRootDirectory) ) {
            throw new \Exception(sprintf('This folder system %s does not exit', $this->downloadRootDirectory));
        }

        if (!is_dir($this->downloadRootDirectory)) {
            throw new \Exception('This path is not directory');
        }

        if(!is_array($fileExtensions)){
            throw new \Exception(sprintf('File extension is not a array. This value is %s', $fileExtensions));
        }

        $files = $this->getPartialDownloadFiles($fileExtensions);
        foreach($files as $file) {
            unlink($file);
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
    public function countFilesByExtension($fileExtensions = array ('crdownload'))
    {
        if ( !file_exists($this->downloadRootDirectory) ) {
            throw new \Exception(sprintf('This folder system %s does not exit', $this->downloadRootDirectory));
        }

        if (!is_dir($this->downloadRootDirectory)) {
            throw new \Exception('This path is not directory');
        }

        if (!is_array($fileExtensions)) {
            throw new \Exception(sprintf('File extension is not a array. This value is %s', $fileExtensions));
        }

        $files = $this->getPartialDownloadFiles($fileExtensions);

        return count($files);
    }

    /**
     * Click to download button element and waiting for finishing download
     * @param RemoteWebElement $clickableElement
     *
     * @return $this
     */
    public function downloadThenWaitUntilComplete(RemoteWebElement $clickableElement)
    {
        $currentPartialDownloadCount = $this->countFilesByExtension();

        $this->logger->info('Click to download element');
        $clickableElement->click();
        $this->waitFinishingDownload($currentPartialDownloadCount);

        return $this;
    }

    /**
     * Do wait until download complete
     * @param int $currentPartialDownloadCount
     * @return $this
     */
    public function waitFinishingDownload($currentPartialDownloadCount = 0)
    {
        $currentPartialDownloadCount = (int)$currentPartialDownloadCount;
        $this->logger->info(sprintf('Start to wait for data download with currentPartialDownloadCount = %d', $currentPartialDownloadCount));

        $foundPartialFile = false;
        $totalWaitTime = 0;

        do {

            $files = $this->getPartialDownloadFiles();
            $partialDownloadCount = count($files);

            if ($foundPartialFile === false && ($partialDownloadCount < $currentPartialDownloadCount)) {
                $currentPartialDownloadCount = $partialDownloadCount; //!important update $currentPartialDownloadCount if the previous download complete and partial download file is removed
            }

            if ($foundPartialFile === false && ($partialDownloadCount > $currentPartialDownloadCount)) {
                $foundPartialFile = true;
            }

            if ($foundPartialFile == false) {
                usleep(10);
                $totalWaitTime += 10/1000000;

                if ($totalWaitTime > $this->downloadTimeout) {
                    $this->logger->info(sprintf('Break because Time out %d', $totalWaitTime));
                    break;
                }

                continue;
            }

            $this->logger->info(sprintf('Found %d partial download files', $partialDownloadCount));

            if ($foundPartialFile == true && $partialDownloadCount <= $currentPartialDownloadCount) { // download complete
                $this->logger->info('Download complete');
                break;
            }

            $this->logger->info('Waiting for 5 seconds to see if download complete');

            sleep(self::RESCAN_TIMEOUT);
            $totalWaitTime += self::RESCAN_TIMEOUT;

            $this->logger->info(sprintf('Wait complete due to timeout (yes/no) %d', $totalWaitTime >= $this->downloadTimeout));
        }
        while ($totalWaitTime < $this->downloadTimeout);

        return $this;
    }

    /**
     * @param array $fileExtensions
     * @return array
     */
    private function getPartialDownloadFiles($fileExtensions = array ('crdownload'))
    {
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->downloadRootDirectory, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        $expectFiles = [];
        /** @var  SplFileInfo $fileInfo */
        foreach ($files as $fileInfo) {
            if (!in_array($fileInfo->getExtension(), $fileExtensions)) {
                continue;
            }

            $expectFiles = $fileInfo->getRealPath();
        }

        return $expectFiles;
    }
}