<?php

namespace Tagcade\Service\Fetcher;

use Facebook\WebDriver\Remote\RemoteWebDriver;

interface UpdatingPasswordInterface
{
    /**
     * ignore Updating Password
     *
     * @param RemoteWebDriver $driver
     * @return mixed
     */
    public function ignoreUpdatingPassword(RemoteWebDriver $driver);
}