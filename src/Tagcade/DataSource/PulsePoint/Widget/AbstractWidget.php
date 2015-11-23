<?php

namespace Tagcade\DataSource\PulsePoint\Widget;

use Facebook\WebDriver\Remote\RemoteWebDriver;

abstract class AbstractWidget
{
    /**
     * @var RemoteWebDriver
     */
    protected $driver;

    /**
     * @param RemoteWebDriver $driver
     */
    public function __construct(RemoteWebDriver $driver)
    {
        $this->driver = $driver;
    }
}