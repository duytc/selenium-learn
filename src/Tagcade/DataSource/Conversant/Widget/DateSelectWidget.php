<?php


namespace Tagcade\DataSource\Conversant\Widget;


use DateTime;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverSelect;
use Monolog\Logger;

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
        $this->driver
            ->findElement(WebDriverBy::cssSelector("input[name='report-dateRange-start']"))
            ->sendKeys($startDate->format('m-d-Y'))
        ;
    }

    /**
     * @param DateTime $endDate
     */
    protected function setEndDate(DateTime $endDate)
    {
        $this->driver
            ->findElement(WebDriverBy::cssSelector("input[name='report-dateRange-end']"))
            ->sendKeys($endDate->format('m-d-Y'))
        ;
    }
}