<?php

namespace Tagcade\Service\Fetcher;

use Facebook\WebDriver\Remote\RemoteWebDriver;

interface UpdatingPasswordInterface
{
    public function ignoreUpdatingPassword(RemoteWebDriver $driver);
}