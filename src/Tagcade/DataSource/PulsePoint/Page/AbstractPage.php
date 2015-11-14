<?php

namespace Tagcade\DataSource\PulsePoint\Page;

use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;

abstract class AbstractPage
{
    /**
     * @var RemoteWebDriver
     */
    protected $driver;

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
}