<?php

namespace Tagcade\DataSource\PulsePoint\Widget;

use Facebook\WebDriver\Remote\RemoteWebDriver;
use Monolog\Logger;

abstract class AbstractWidget
{
    /**
     * @var RemoteWebDriver
     */
    protected $driver;
    /**
     * @var Logger
     */
    protected  $logger;

    /**
     * @param RemoteWebDriver $driver
     * @param Logger $logger
     */
    public function __construct(RemoteWebDriver $driver , Logger $logger =null)
    {
        $this->driver = $driver;
        $this->logger = $logger;
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