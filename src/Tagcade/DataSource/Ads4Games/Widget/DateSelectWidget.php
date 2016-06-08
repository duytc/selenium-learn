<?php


namespace Tagcade\DataSource\Ads4Games\Widget;


use DateTime;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverSelect;

class DateSelectWidget extends AbstractWidget {

    const OPTION_SPECIFIC_VALUE     = 'specific';

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
        $dateRangeElement = new WebDriverSelect($this->driver->findElement(WebDriverBy::id('period_preset')));
        $dateRangeElement->selectByValue(self::OPTION_SPECIFIC_VALUE);

        $this->setStartDate($startDate);
        $this->setEndDate($endDate);

        return $this;
    }

    /**
     * @param DateTime $startDate
     */
    protected function setStartDate(DateTime $startDate )
    {
        $this->driver->findElement(WebDriverBy::id('period_start'))->clear()->sendKeys($startDate->format('d-m-Y'));
    }

    /**
     * @param DateTime $endDate
     */
    protected function setEndDate(DateTime $endDate)
    {
        $this->driver->findElement(WebDriverBy::id('period_end'))->clear()->sendKeys($endDate->format('d-m-Y'));
    }
} 