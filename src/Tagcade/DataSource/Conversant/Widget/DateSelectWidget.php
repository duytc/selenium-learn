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
        $y = $startDate->format('Y');
        $m = $startDate->format('m');
        $d = $startDate->format('d');

        if($y == 2011) {
            $m = $m - 1;
        }

        $this->driver->findElement(WebDriverBy::id("search-start_date"))->click();

        $this->driver->findElement(WebDriverBy::cssSelector("#ui-datepicker-div > div > div > select.ui-datepicker-year"))->click();
        $this->driver->findElement(WebDriverBy::xpath("//option[text()[contains(.,'".$y."')]]"))->click();

        $this->driver->findElement(WebDriverBy::cssSelector("#ui-datepicker-div > div > div > select.ui-datepicker-month"))->click();
        $this->driver->findElement(WebDriverBy::cssSelector("#ui-datepicker-div > div > div > select.ui-datepicker-month > option:nth-child(".($m).")"))->click();

        $this->driver->findElement(WebDriverBy::xpath("//a[text()[contains(.,'".number_format($d)."')]]"))->click();
    }

    /**
     * @param DateTime $endDate
     */
    protected function setEndDate(DateTime $endDate)
    {
        $y = $endDate->format('Y');
        $m = $endDate->format('m');
        $d = $endDate->format('d');

        if($y == 2011) {
            $m = $m - 1;
        }

        $this->driver->findElement(WebDriverBy::id("search-end_date"))->click();

        $this->driver->findElement(WebDriverBy::cssSelector("#ui-datepicker-div > div > div > select.ui-datepicker-year"))->click();
        $this->driver->findElement(WebDriverBy::xpath("//option[text()[contains(.,'".$y."')]]"))->click();

        $this->driver->findElement(WebDriverBy::cssSelector("#ui-datepicker-div > div > div > select.ui-datepicker-month"))->click();
        $this->driver->findElement(WebDriverBy::cssSelector("#ui-datepicker-div > div > div > select.ui-datepicker-month > option:nth-child(".($m).")"))->click();

        $this->driver->findElement(WebDriverBy::xpath("//a[text()[contains(.,'".number_format($d)."')]]"))->click();
    }
}