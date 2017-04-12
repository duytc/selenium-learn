<?php

namespace Tagcade;

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
     * @param null|string $rootDownloadDir
     * @param null|string $subDir the sub dir (last dir) before the file. This is for metadata comprehension mechanism
     * @return bool|RemoteWebDriver
     */
    public function getWebDriver($identifier, $rootDownloadDir = null, $subDir = null);

    public function getLastSessionId();

    /**
     * @param string $rootDownloadDir
     * @param null|string $subDir
     * @return RemoteWebDriver
     */
    public function createWebDriver($rootDownloadDir, $subDir = null);

    /**
     * @param array $config
     * @throws \Exception
     */
    public function setConfig(array $config);

    /**
     * @param PartnerParamInterface $params
     */
    public function setParams($params);

    public function clearAllSessions();
}