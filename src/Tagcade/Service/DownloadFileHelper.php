<?php

namespace Tagcade\Service;


use Exception;
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
	/**
	 * @var
	 */
	private $rootKernelDirectory;

	function __construct($downloadRootDirectory, $downloadTimeout, LoggerInterface $logger, $rootKernelDirectory)
    {
        $this->downloadRootDirectory = sprintf('%s/', $downloadRootDirectory);
        $this->downloadTimeout = $downloadTimeout;
        $this->logger = $logger;
	    $this->rootKernelDirectory = $rootKernelDirectory;
    }

    /**
     * @inheritdoc
     */
    public function deleteFilesByExtension($fileExtensions = array ('crdownload'))
    {
        if ( !file_exists($this->downloadRootDirectory) ) {
            throw new Exception(sprintf('This folder system %s does not exist', $this->downloadRootDirectory));
        }

        if (!is_dir($this->downloadRootDirectory)) {
            throw new Exception('This path is not directory');
        }

        if(!is_array($fileExtensions)){
            throw new Exception(sprintf('File extension is not a array. This value is %s', $fileExtensions));
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
            $this->logger->error("Invalid remove web element");
            return $this;
        }

        if (!is_dir($directoryStoreDownloadFile)) {
            $this->logger->error(sprintf('Path to store data download is not directory, %s', $directoryStoreDownloadFile));
            return $this;
        }

        $this->logger->debug('Click to download element');
        $oldFiles = $this->getAllFilesInDirectory($directoryStoreDownloadFile);
        $clickAbleElement->click();
        $this->waitFinishingDownload($directoryStoreDownloadFile, $oldFiles) ;

        return $this;
    }

    /**
     * @param int $directoryStoreDownloadFile
     * @param $oldFiles
     * @throws \Exception
     * @internal param $totalOldFiles
     * @return $this
     */
    public function waitFinishingDownload($directoryStoreDownloadFile, $oldFiles)
    {
        $countOldFiles = count($oldFiles);
        $foundPartialFile = false;
        $totalWaitTime = 0;

        $this->logger->debug(sprintf('Start to wait for data download with $countOldFiles = %d, path to store downloadFile =%s', $countOldFiles, $directoryStoreDownloadFile));
        if (!is_dir($directoryStoreDownloadFile)) {
            $this->logger->error(sprintf('Path to store data download is not directory, %s', $directoryStoreDownloadFile));
            return $this;
        }

        do {

            $currentPartialDownloadFiles = $this->getPartialDownloadFiles($directoryStoreDownloadFile);
            $currentPartialDownloadCount = count($currentPartialDownloadFiles);

            if ($foundPartialFile === false && ($currentPartialDownloadCount > 0)) {
                $foundPartialFile = true;
            }

            if ($foundPartialFile == false) {
                $allFiles = $this->getAllFilesInDirectory($directoryStoreDownloadFile);
                $countCurrentFiles = count($allFiles);

	            $this->logger->debug(sprintf('path store file %s', $directoryStoreDownloadFile));
                $this->logger->debug(sprintf('Now total files = %d', $countCurrentFiles));

                if ($countCurrentFiles > $countOldFiles) {
                    $this->logger->debug('File has been downloaded!');
                    break;
                }

                usleep(static::NO_PARTIAL_FILE_RESCAN_TIME_IN_SECONDS * 1000000);
                $totalWaitTime += static::NO_PARTIAL_FILE_RESCAN_TIME_IN_SECONDS;

                if ($totalWaitTime > $this->downloadTimeout) {
                    $this->logger->warning(sprintf('Break because of time out after %f seconds. File has not been download', $totalWaitTime));
                    break;
                }

                $this->logger->debug(sprintf('Continue wait, total waiting time: %f seconds', $totalWaitTime));
                continue;
            }

            $this->logger->debug(sprintf('Found %d partial download files', $currentPartialDownloadCount));

            if ( $foundPartialFile == true && $currentPartialDownloadCount ==0 ) { // download complete
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
     * @param $downloadDirectory
     * @param array $fileExtensions
     * @return array
     */
    private function getPartialDownloadFiles($downloadDirectory, $fileExtensions = array ('crdownload'))
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
        if(!is_dir($downloadDirectory)) {
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