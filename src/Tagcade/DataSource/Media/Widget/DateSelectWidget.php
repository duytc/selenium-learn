<?php

namespace Tagcade\DataSource\Media\Widget;

use DateTime;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;

class DateSelectWidget extends AbstractWidget
{
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

    protected function setStartDate(DateTime $startDate )
    {
        $this->driver->findElement(WebDriverBy::id('from'))->clear()->sendKeys($startDate->format('m/d/y'));
    }

    protected function setEndDate(DateTime $endDate)
    {
        $this->driver->findElement(WebDriverBy::id('to'))->clear()->sendKeys($endDate->format('m/d/y'));
    }

}