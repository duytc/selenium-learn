<?php

namespace Tagcade\DataSource\Sovrn\Widget;

use DateTime;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;

class DateSelectWidget extends AbstractWidget
{
    /**
     * @var string
     */
    private $fieldId;

    /**
     * @param RemoteWebDriver $driver
     */
    public function __construct(RemoteWebDriver $driver)
    {
        parent::__construct($driver);
    }

    /**
     * @param DateTime $date
     * @return $this
     * @throws \Exception
     * @throws \Facebook\WebDriver\Exception\NoSuchElementException
     * @throws \Facebook\WebDriver\Exception\TimeOutException
     * @throws null
     */
    public function setDate(DateTime $date)
    {
        $currentYear = $this->getCurrentYear();
        $expectYear = $date->format('Y');
        $yearCounter = $currentYear != $expectYear ? ($currentYear > $expectYear ? - 1 : 1) : 0;
        while ($currentYear != $expectYear) {
            $cssSelect = $yearCounter < 0 ? "#calendar_downloads_account-downloads-adstats .k-btn-previous-month" : "#calendar_downloads_account-downloads-adstats .k-btn-next-month";
            $this->driver->findElement(WebDriverBy::cssSelector($cssSelect))
                ->click()
            ;

            usleep(10);
            $currentYear = $this->getCurrentYear();
        }

        $currentMonth = $this->getCurrentMonth();
        $expectMonth = (int)$date->format('m');
        $monthCounter = $currentMonth != $expectMonth ? ($currentMonth > $expectMonth ? - 1 : 1) : 0;
        while ($currentMonth != $expectMonth) {
            $cssSelect = $monthCounter < 0 ? "#calendar_downloads_account-downloads-adstats .k-btn-previous-month" : "#calendar_downloads_account-downloads-adstats .k-btn-next-month";
            $this->driver->findElement(WebDriverBy::cssSelector($cssSelect))
                ->click()
            ;
            usleep(10);
            $currentMonth = $this->getCurrentMonth();
        }

        $expectDay = $date->format('Y-m-d');
        $cssSelectDay = sprintf('#calendar_downloads_account-downloads-adstats span[data-date="%s"]', $expectDay);
        $this->driver->wait()->until(
            WebDriverExpectedCondition::elementToBeClickable(WebDriverBy::cssSelector($cssSelectDay))
        );
        // Select date
        $this->driver->findElement(WebDriverBy::cssSelector($cssSelectDay))
            ->click()
        ;

        return $this;
    }

    /**
     * @return DateTime
     */
    protected function getCurrentDate()
    {
        $currentDate = $this->driver->findElement(WebDriverBy::cssSelector('#calendar_downloads_account-downloads-adstats .k-caption'))
            ->getText()
        ;

        return DateTime::createFromFormat('F, Y', $currentDate);
    }

    protected function getCurrentYear()
    {
        $currentDate = $this->getCurrentDate();

        return $currentDate->format('Y');
    }

    protected function getCurrentMonth()
    {
        $currentDate = $this->getCurrentDate();

        return (int)$currentDate->format('m');
    }


}