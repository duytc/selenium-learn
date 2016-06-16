<?php


namespace Tagcade\DataSource\Ads4Games\Widget;


use DateTime;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverSelect;
use Monolog\Logger;

class DateSelectWidget extends AbstractWidget {

    const CUSTOM_RANGE_VALUE     = 'Custom range...';

    /**
     * @param RemoteWebDriver $driver
     */
    public function __construct(RemoteWebDriver $driver, Logger $logger)
    {
        parent::__construct($driver, $logger);
    }

    /**
     * @param DateTime $startDate
     * @param DateTime $endDate
     * @return $this
     */
    public function setDateRange(DateTime $startDate, DateTime $endDate)
    {
        $this->logger->info('Go to set date range');
        $dateRangeCss = '#ext-gen1246';
        $this->driver->findElement(WebDriverBy::cssSelector($dateRangeCss))->click();
        $customRange = '#boundlist-1083-listEl > ul > li:nth-child(12)';
        $this->driver->findElement(WebDriverBy::cssSelector($customRange))->click();

        $this->setStartDate($startDate);
        $this->setEndDate($endDate);

        return $this;
    }

    /**
     * @param DateTime $startDate
     */
    protected function setStartDate(DateTime $startDate )
    {
        $this->driver->findElement(WebDriverBy::id('analytics-customFrom-inputEl'))->clear()->sendKeys($startDate->format('d-m-Y'));
    }

    /**
     * @param DateTime $endDate
     */
    protected function setEndDate(DateTime $endDate)
    {
        $this->driver->findElement(WebDriverBy::id('analytics-customTo-inputEl'))->clear()->sendKeys($endDate->format('d-m-Y'));
    }
} 