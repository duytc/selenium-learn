<?php


namespace Tagcade\Service;


use Facebook\WebDriver\Remote\RemoteWebElement;

interface DownloadFileHelperInterface
{
    /**
     * Delete files by extension
     *
     * @param string $fileExtension
     * @return mixed
     */
    public function deleteFilesByExtension($fileExtension = '.crdownload');

    /**
     * Waiting download file finish
     *
     * @param $directoryStoreDownloadFile
     * @param $totalOldFiles
     * @return $this
     */
    public function waitFinishingDownload( $directoryStoreDownloadFile, $totalOldFiles );

    /**
     * @param RemoteWebElement $clickAbleElement
     * @param $directoryStoreDownloadFile
     * @return mixed
     */
    public function downloadThenWaitUntilComplete(RemoteWebElement $clickAbleElement, $directoryStoreDownloadFile);

    /**
     * Return root directory
     * @return mixed
     */
    public function getRootDirectory();
}