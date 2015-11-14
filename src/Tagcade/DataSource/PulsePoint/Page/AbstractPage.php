<?php

namespace Tagcade\DataSource\PulsePoint\Page;

use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Psr\Log\LoggerInterface;

abstract class AbstractPage
{
    /**
     * @var RemoteWebDriver
     */
    protected $driver;
    /**
     * @var LoggerInterface
     */
    protected $logger;

    public function __construct(RemoteWebDriver $driver)
    {
        $this->driver = $driver;
    }

    /**
     * @param bool $force Force reload even if the url is the current url
     * @return $this
     */
    public function navigate($force = false)
    {
        if ($this->isCurrentUrl() && !$force) {
            return $this;
        }

        $this->driver->navigate()->to(static::URL);

        return $this;
    }

    /**
     * @return bool
     */
    public function isCurrentUrl()
    {
        return strpos($this->driver->getCurrentURL(), static::URL) === 0;
    }

    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @return bool
     */
    protected function hasLogger()
    {
        return $this->logger instanceof LoggerInterface;
    }

    protected function waitForAjax()
    {
        if ($this->hasLogger()) {
            $this->logger->info('Waiting for ajax to load');
        }

        $this->driver->wait()->until(function (RemoteWebDriver $driver) {
            return $driver->executeScript("return !!window.jQuery && window.jQuery.active == 0");
        });

        if ($this->hasLogger()) {
            $this->logger->info('Waiting for overlay to disappear');
        }

        $this->driver->wait(30, 2500)->until(
            WebDriverExpectedCondition::not(WebDriverExpectedCondition::presenceOfAllElementsLocatedBy(WebDriverBy::cssSelector('div.blockUI')))
        );
    }
}