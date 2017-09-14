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

    /**
     * @param $downloadDirectory
     * @return mixed
     */
    public function getAllFilesInDirectory($downloadDirectory);
}