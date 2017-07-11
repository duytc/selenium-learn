<?php

namespace Tagcade;

use Exception;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Tagcade\Service\Fetcher\Params\PartnerParamInterface;

interface WebDriverFactoryInterface
{
    /**
     * @param string $sessionId
     * @return bool|RemoteWebDriver
     */
    public function getExistingSession($sessionId);

    /**
     * @param String $identifier session id or data path
     * @param null|string $defaultDownloadPath
     * @return bool|RemoteWebDriver
     */
    public function getWebDriver($identifier, $defaultDownloadPath = null);

    public function getLastSessionId();

    /**
     * @param string $defaultDownloadPath
     * @return RemoteWebDriver
     */
    public function createWebDriver($defaultDownloadPath);

    /**
     * @param array $config
     * @throws Exception
     */
    public function setConfig(array $config);

    /**
     * @param PartnerParamInterface $params
     */
    public function setParams($params);
}