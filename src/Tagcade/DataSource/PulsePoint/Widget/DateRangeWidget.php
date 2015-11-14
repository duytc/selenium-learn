<?php

namespace Tagcade\DataSource\PulsePoint\Widget;

use DateTime;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;

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

    /**
     * @param DateTime $startDate
     * @param DateTime $endDate
     * @return $this
     */
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

        return $this;
    }
}