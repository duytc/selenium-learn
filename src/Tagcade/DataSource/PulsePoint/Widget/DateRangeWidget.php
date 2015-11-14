<?php

namespace Tagcade\DataSource\PulsePoint\Widget;

use DateTime;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Facebook\WebDriver\WebDriverSelect;

class DateRangeWidget
{
    /**
     * @var RemoteWebDriver
     */
    private $driver;

    /**
     * @param RemoteWebDriver $driver
     */
    public function __construct(RemoteWebDriver $driver)
    {
        $this->driver = $driver;
    }

    public function setDateRange(DateTime $startDate, DateTime $endDate = null)
    {
        $endDate = $endDate ?: $startDate;

        $this->driver->findElement(WebDriverBy::id('rbCustomDates'))->click();

        (new DateSelectWidget($this->driver, 'txtStartDate'))
            ->setDate($startDate)
        ;

        (new DateSelectWidget($this->driver, 'txtEndDate'))
            ->setDate($endDate)
        ;
    }
}