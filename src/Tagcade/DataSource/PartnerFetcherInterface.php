<?php

namespace Tagcade\DataSource;


use Facebook\WebDriver\Remote\RemoteWebDriver;
use Psr\Log\LoggerInterface;
use Tagcade\Service\DownloadFileHelperInterface;

interface PartnerFetcherInterface
{
    /**
     * @return DownloadFileHelperInterface
     */
    public function getDownloadFileHelper();

    /**
     * @param DownloadFileHelperInterface $downloadFileHelper
     */
    public function setDownloadFileHelper(DownloadFileHelperInterface $downloadFileHelper);

    /**
     * @return LoggerInterface
     */
    public function getLogger();

    /**
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger);

    /**
     * @param PartnerParamInterface $params
     * @param RemoteWebDriver $driver
     * @return mixed
     */
    public function getAllData(PartnerParamInterface $params, RemoteWebDriver $driver);

    /**
     * @return string
     */
    public function getName();

    /**
     * @param mixed $name
     */
    public function setName($name);
}