<?php


namespace Tagcade\DataSource\Adtech\Widget;


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
        $this->driver->findElement(WebDriverBy::cssSelector('#option\28 from\29'))->clear()->sendKeys($startDate->format('m/d/y'));
    }

    /**
     * @param DateTime $endDate
     */
    protected function setEndDate(DateTime $endDate)
    {
        $this->driver->findElement(WebDriverBy::cssSelector('#option\28 to\29'))->clear()->sendKeys($endDate->format('m/d/y'));
    }
} 