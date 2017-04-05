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
     * @param null $dataPath
     * @return bool|RemoteWebDriver
     */
    public function getWebDriver($identifier, $dataPath = null);

    public function getLastSessionId();

    /**
     * @param $dataPath
     * @return RemoteWebDriver
     */
    public function createWebDriver($dataPath);

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