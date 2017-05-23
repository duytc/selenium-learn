<?php

namespace Tagcade\Service\Fetcher;


use Exception;
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
     * do login
     *
     * @param PartnerParamInterface $params
     * @param RemoteWebDriver $driver
     * @param $needToLogin
     * @return bool true if logged in successfully
     * @throws Exception when login fail or other exception
     */
    public function doLogin(PartnerParamInterface $params, RemoteWebDriver $driver, $needToLogin = false);

    /**
     * @param PartnerParamInterface $params
     * @param RemoteWebDriver $driver
     * @return mixed
     */
    public function doLogout(PartnerParamInterface $params, RemoteWebDriver $driver);

    /**
     * download report data based on given params and save report files to pre-configured directory
     * @param PartnerParamInterface $params
     * @param RemoteWebDriver $driver
     * @return void
     */
    public function getAllData(PartnerParamInterface $params, RemoteWebDriver $driver);
}