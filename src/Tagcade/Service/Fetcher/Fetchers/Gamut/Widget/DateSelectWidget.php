<?php

namespace Tagcade\Service\Fetcher\Fetchers\Gamut\Widget;


use DateTime;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverSelect;
use Tagcade\Service\Fetcher\Fetchers\PulsePoint\Widget\AbstractWidget;

class DateSelectWidget extends AbstractWidget
{
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
    protected function setStartDate(DateTime $startDate)
    {
        $monthElement = $this->driver->findElement(WebDriverBy::id('ctl00_ctl00_PageLayoutBody_BodyContent_DateRange_startDateCalendar_Month'));
        $monthSelect = new WebDriverSelect($monthElement);
        $m = $startDate->format("n");
        $monthSelect->selectByValue($m);
        $dayElement = $this->driver->findElement(WebDriverBy::id('ctl00_ctl00_PageLayoutBody_BodyContent_DateRange_startDateCalendar_Day'));
        $daySelect = new WebDriverSelect($dayElement);
        $d = $startDate->format("j");
        $daySelect->selectByValue($d);
        $yearElement = $this->driver->findElement(WebDriverBy::id('ctl00_ctl00_PageLayoutBody_BodyContent_DateRange_startDateCalendar_Year'));
        $yearSelect = new WebDriverSelect($yearElement);
        $y = $startDate->format("Y");
        $yearSelect->selectByValue($y);
    }

    /**
     * @param DateTime $endDate
     */
    protected function setEndDate(DateTime $endDate)
    {
        $monthElement = $this->driver->findElement(WebDriverBy::id('ctl00_ctl00_PageLayoutBody_BodyContent_DateRange_endDateCalendar_Month'));
        $monthSelect = new WebDriverSelect($monthElement);
        $m = $endDate->format("n");
        $monthSelect->selectByValue($m);
        $dayElement = $this->driver->findElement(WebDriverBy::id('ctl00_ctl00_PageLayoutBody_BodyContent_DateRange_endDateCalendar_Day'));
        $daySelect = new WebDriverSelect($dayElement);
        $d = $endDate->format("j");
        $daySelect->selectByValue($d);
        $yearElement = $this->driver->findElement(WebDriverBy::id('ctl00_ctl00_PageLayoutBody_BodyContent_DateRange_endDateCalendar_Year'));
        $yearSelect = new WebDriverSelect($yearElement);
        $y = $endDate->format("Y");
        $yearSelect->selectByValue($y);
    }
}