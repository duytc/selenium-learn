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

    /**
     * @param $currentDate
     * @param $expectDate
     * @return bool
     */

    public function isPrevioustNavigator($currentDate, $expectDate )
    {

        $currentMonth = date('m', strtotime($currentDate));
        $currentYear = date('Y', strtotime($currentDate));

        $expectMonth = date('m', strtotime($expectDate));
        $expectYear = date('Y', strtotime($expectDate));;

        if(( (int)$currentYear > (int)$expectYear) || ($currentYear == $expectYear && $currentMonth > $expectMonth)) {
            return true;
        }

        return false;

    }


}