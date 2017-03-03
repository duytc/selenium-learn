<?php

namespace Tagcade\Service\Fetcher\Fetchers\PulsePoint;

use Facebook\WebDriver\Remote\RemoteWebDriver;
use Tagcade\WebDriverFactoryInterface;
use DateTime;

interface TaskFactoryInterface
{
    /**
     * @return WebDriverFactoryInterface
     */
    public function getWebDriverFactory();

    public function getAllData(TaskParams $params, RemoteWebDriver $driver = null);

    /**
     * @param $username
     * @param $password
     * @param $email
     * @param DateTime $date
     * @return TaskParams
     */
    public function createParams($username, $password, $email, DateTime $date);
}