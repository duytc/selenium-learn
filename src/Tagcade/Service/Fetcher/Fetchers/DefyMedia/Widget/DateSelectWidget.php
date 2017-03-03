<?php

namespace Tagcade\Service\Fetcher\Fetchers\DefyMedia\Widget;

use DateTime;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;
use Monolog\Logger;

class DateSelectWidget extends AbstractWidget
{
    /**
     * @param RemoteWebDriver $driver
     * @param Logger $logger
     */
    public function __construct(RemoteWebDriver $driver, Logger $logger = null)
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
       $this->selectDate($startDate, '.left');
       $this->selectDate($endDate, '.right');

        $this->driver->findElement(WebDriverBy::cssSelector('.applyBtn'))
            ->click()
        ;

        return $this;
    }

    protected function selectDate(DateTime $date, $leftRight = '.left')
    {
        $currentDate = $this->getCurrentDate($leftRight);
        $currentYear = $currentDate->format('Y');
        $expectYear = $date->format('Y');
        $yearCounter = $currentYear != $expectYear ? ($currentYear > $expectYear ? - 1 : 1) : 0;
        while ($currentYear != $expectYear) {
            $cssSelect = $yearCounter < 0 ? "$leftRight .prev" : "$leftRight .next";
            $this->driver->findElement(WebDriverBy::cssSelector($cssSelect))
                ->click()
            ;

            usleep(10);
            $currentDate = $this->getCurrentDate($leftRight);
            $currentYear = $currentDate->format('Y');
            usleep(10);
        }

        $currentMonth = (int)$currentDate->format('m');
        $expectMonth = (int)$date->format('m');
        $monthCounter = $currentMonth != $expectMonth ? ($currentMonth > $expectMonth ? - 1 : 1) : 0;
        while ($currentMonth != $expectMonth) {
            $cssSelect = $monthCounter < 0 ? "$leftRight .prev" : "$leftRight .next";

            $this->driver->findElement(WebDriverBy::cssSelector($cssSelect))
                ->click()
            ;
            usleep(10);
            $currentDate = $this->getCurrentDate($leftRight);
            $currentMonth = (int)$currentDate->format('m');
            usleep(10);
        }

        // Select date
        $expectDay = (int)$date->format('d');
        $days = $this->driver->findElements(WebDriverBy::cssSelector("$leftRight td:not(.off)"));
        foreach ($days as $d) {
            $currentDay = $d->getText();
            if ($currentDay == $expectDay) {
                $d->click();
                break;
            }
        }

        return $this;
    }

    protected function getCurrentDate($leftRight)
    {
        $currentDate = $this->driver->findElement(WebDriverBy::cssSelector(sprintf('%s th:nth-child(2)', $leftRight)))
            ->getText()
        ;

        return DateTime::createFromFormat('M Y', $currentDate);
    }
}