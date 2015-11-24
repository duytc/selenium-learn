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
     * @param String $dataPath
     * @return bool|RemoteWebDriver
     */
    public function getWebDriver($dataPath);

    /**
     * @param $dataPath
     * @return RemoteWebDriver
     */
    public function createWebDriver($dataPath);
}