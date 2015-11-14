<?php

namespace Tagcade\DataSource\PulsePoint\WebDriver;

use Facebook\WebDriver\Remote\RemoteWebDriver;

class WebDriverExpectedCondition
{
    public static function jQueryInactive()
    {
        return function (RemoteWebDriver $driver) {
            return $driver->executeScript("return !!window.jQuery && window.jQuery.active == 0");
        };
    }
}