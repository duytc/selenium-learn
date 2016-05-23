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
     * @return $this
     *
     */
    public function waitFinishingDownload($currentPartialDownloadCount = 0);

    /**
     * @param RemoteWebElement $clickableElement
     * @return mixed
     */
    public function downloadThenWaitUntilComplete(RemoteWebElement $clickableElement);
}