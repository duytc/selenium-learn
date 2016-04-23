<?php

namespace Tagcade;

use Facebook\WebDriver\Remote\RemoteWebDriver;

interface WebDriverFactoryInterface
{
    /**
     * @param string $sessionId
     * @return bool|RemoteWebDriver
     */
    public function getExistingSession($sessionId);

    /**
     * @param String $identifier session id or data path
     * @return bool|RemoteWebDriver
     */
    public function getWebDriver($identifier);

    /**
     * @param $dataPath
     * @return RemoteWebDriver
     */
    public function createWebDriver($dataPath);
}