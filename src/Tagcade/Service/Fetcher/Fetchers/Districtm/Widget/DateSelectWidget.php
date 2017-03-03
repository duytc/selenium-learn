<?php


namespace Tagcade\Service\Fetcher\Fetchers\Districtm\Widget;


use DateTime;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;

class DateSelectWidget extends AbstractWidget {

    /**
     * @param RemoteWebDriver $driver
     */
    public function __construct(RemoteWebDriver $driver)
    {
        parent::__construct($driver);
    }

    /**
     * @param DateTime $startDate
     * @param DateTime $endDate
     * @return $this
     */
    public function setDateRange(DateTime $startDate, DateTime $endDate)
    {
        $this->setStartDate($startDate);
        $this->setEndDate($endDate);

        return $this;
    }

    /**
     * @param DateTime $startDate
     */
    protected function setStartDate(DateTime $startDate )
    {
        $this->driver->findElement(WebDriverBy::id('startDate'))->clear()->sendKeys($startDate->modify('+1 day')->format('Y-m-d'));
    }

    /**
     * @param DateTime $endDate
     */
    protected function setEndDate(DateTime $endDate)
    {
        $this->driver->findElement(WebDriverBy::id('endDate'))->clear()->sendKeys($endDate->modify('+1 day')->format('Y-m-d'));
    }
} 