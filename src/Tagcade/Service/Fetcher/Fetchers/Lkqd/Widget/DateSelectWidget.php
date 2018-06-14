<?php


namespace Tagcade\Service\Fetcher\Fetchers\Lkqd\Widget;


use DateTime;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;

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
        //open the date picker
        $this->driver->findElement(WebDriverBy::xpath('//lkqd-date-range'))->click();

        $this->setStartDate($startDate);
        $this->setEndDate($endDate);
        $this->apply();
        return $this;
    }

    /**
     * @param DateTime $startDate
     */
    protected function setStartDate(DateTime $startDate )
    {
        $this->driver->findElement(WebDriverBy::xpath('//input[@name="daterangepicker_start"]'))->clear()->sendKeys($startDate->format('m/d/Y'));
    }

    /**
     * @param DateTime $endDate
     */
    protected function setEndDate(DateTime $endDate)
    {
        $this->driver->findElement(WebDriverBy::xpath('//input[@name="daterangepicker_end"]'))->clear()->sendKeys($endDate->format('m/d/Y'));
    }

    protected function apply()
    {
        $this->driver->findElement(WebDriverBy::xpath('//div[@class="range_inputs"]/button[contains(@class, "applyBtn")]'))->click();
    }
} 