<?php

namespace Tagcade\Service;


use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Remote\RemoteWebElement;
use Facebook\WebDriver\WebDriverBy;
use Psr\Log\LoggerInterface;
use Symfony\Component\Finder\SplFileInfo;

class DownloadFileHelper implements DownloadFileHelperInterface  {

    const RESCAN_TIME_IN_SECONDS = 5;
    const NO_PARTIAL_FILE_RESCAN_TIME_IN_SECONDS = 0.25;
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

        $this->logger->debug('Click to download element');
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
        $this->logger->debug(sprintf('Start to wait for data download with currentPartialDownloadCount = %d', $currentPartialDownloadCount));

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
                usleep(static::NO_PARTIAL_FILE_RESCAN_TIME_IN_SECONDS * 1000000);
                $totalWaitTime += static::NO_PARTIAL_FILE_RESCAN_TIME_IN_SECONDS;

                if ($totalWaitTime > $this->downloadTimeout) {
                    $this->logger->debug(sprintf('Break because of time out after %f seconds', $totalWaitTime));
                    break;
                }

                $this->logger->debug(sprintf('Continue wait, total waiting time: %f seconds', $totalWaitTime));
                continue;
            }

            $this->logger->debug(sprintf('Found %d partial download files', $partialDownloadCount));

            if ($foundPartialFile == true && $partialDownloadCount <= $currentPartialDownloadCount) { // download complete
                $this->logger->debug('Download complete');
                break;
            }

            $this->logger->debug('Waiting for 5 seconds to see if download complete');

            sleep(self::RESCAN_TIME_IN_SECONDS);
            $totalWaitTime += self::RESCAN_TIME_IN_SECONDS;

            $this->logger->debug(sprintf('Wait complete due to timeout (yes/no) %d', $totalWaitTime >= $this->downloadTimeout));
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

    /**
     * @return string
     */
    public  function getRootDirectory ()
    {
        return $this->downloadRootDirectory;
    }
}