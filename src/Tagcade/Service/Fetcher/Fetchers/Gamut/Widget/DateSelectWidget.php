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
        $this->selectDate($startDate);
    }

    /**
     * @param DateTime $endDate
     */
    protected function setEndDate(DateTime $endDate)
    {
        $this->selectDate($endDate);
    }

    private function selectDate(DateTime $date)
    {
        $y = $date->format('Y');
        $m = $date->format('n');
        $d = $date->format('d');

        $monthElement = $this->driver->findElement(WebDriverBy::id('ctl00_ctl00_PageLayoutBody_BodyContent_DateRange_startDateCalendar_Month'));
        $timeZoneSelect = new WebDriverSelect($monthElement);
        $timeZoneSelect->selectByValue($m);
        $dayElement = $this->driver->findElement(WebDriverBy::id('ctl00_ctl00_PageLayoutBody_BodyContent_DateRange_startDateCalendar_Day'));
        $timeZoneSelect = new WebDriverSelect($dayElement);
        $timeZoneSelect->selectByValue($d);
        $yearElement = $this->driver->findElement(WebDriverBy::id('ctl00_ctl00_PageLayoutBody_BodyContent_DateRange_startDateCalendar_Year'));
        $timeZoneSelect = new WebDriverSelect($yearElement);
        $timeZoneSelect->selectByValue($y);
    }
}