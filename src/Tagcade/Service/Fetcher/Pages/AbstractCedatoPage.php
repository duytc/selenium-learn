<?php

namespace Tagcade\Service\Fetcher\Pages;

use Facebook\WebDriver\Remote\RemoteWebDriver;

abstract class AbstractCedatoPage extends AbstractPage
{
    protected $cedatoInternal;

    /**
     * @param RemoteWebDriver $driver
     * @param null $logger
     * @param bool $cedatoInternal
     */
    public function __construct(RemoteWebDriver $driver, $logger = null, $cedatoInternal = false)
    {
        parent::__construct($driver, $logger);
        $this->cedatoInternal = $cedatoInternal;
    }
}