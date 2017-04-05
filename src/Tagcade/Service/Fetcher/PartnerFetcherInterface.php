<?php

namespace Tagcade\Service\Fetcher;


use Facebook\WebDriver\Remote\RemoteWebDriver;
use Psr\Log\LoggerInterface;
use Tagcade\Service\DownloadFileHelperInterface;
use Tagcade\Service\Fetcher\Params\PartnerParamInterface;

interface PartnerFetcherInterface
{
    /**
     * @return DownloadFileHelperInterface
     */
    public function getDownloadFileHelper();

    /**
     * @return LoggerInterface
     */
    public function getLogger();

    /**
     * download report data based on given params and save report files to pre-configured directory
     * @param PartnerParamInterface $params
     * @param RemoteWebDriver $driver
     * @return void
     */
    public function getAllData(PartnerParamInterface $params, RemoteWebDriver $driver);
}