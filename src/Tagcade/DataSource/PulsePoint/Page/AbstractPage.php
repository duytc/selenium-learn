<?php

namespace Tagcade\DataSource\PulsePoint\Page;

use Facebook\WebDriver\Exception\NoSuchElementException;
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

    /**
     * PulsePoint uses a lot of ajax, this function will wait for ajax calls and also for the overlay div
     * to be removed before proceeding
     *
     * @throws NoSuchElementException
     * @throws \Exception
     * @throws \Facebook\WebDriver\Exception\TimeOutException
     */
    public function waitForData()
    {
        if ($this->hasLogger()) {
            $this->logger->info('Waiting for ajax to load');
        }

        $this->driver->wait()->until(function (RemoteWebDriver $driver) {
            return $driver->executeScript("return !!window.jQuery && window.jQuery.active == 0");
        });

        $this->sleep(2);

        $overlayPresent = $this->driver->executeScript("return !!document.querySelector('div.blockUI.blockOverlay')");

        if ($overlayPresent) {
            if ($this->hasLogger()) {
                $this->logger->info('Waiting for overlay to disappear');
            }

            $overlaySel = WebDriverBy::cssSelector('div.blockUI.blockOverlay');
            $this->driver->wait()->until(WebDriverExpectedCondition::invisibilityOfElementLocated($overlaySel));
        }

        if ($this->hasLogger()) {
            $this->logger->info('Overlay has disappeared');
        }
    }

    /**
     * @param double $seconds seconds to sleep for
     */
    public function sleep($seconds)
    {
        $seconds = (double) $seconds;

        if ($seconds <= 0) {
            return;
        }

        if ($this->hasLogger()) {
            $this->logger->debug(sprintf('Waiting for %.1f seconds', $seconds));
        }

        usleep($seconds * 1000 * 1000);
    }
}