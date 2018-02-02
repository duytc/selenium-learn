<?php

namespace Tagcade\Service\Fetcher\Fetchers\StreamRailExternal\Widget;

use DateTime;
use Exception;
use Facebook\WebDriver\WebDriverBy;
use Tagcade\Service\Fetcher\Fetchers\PulsePoint\Widget\AbstractWidget;

class DateSelectWidget extends AbstractWidget
{
    protected $firstDate = false;

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
        sleep(1);

        $startDateElement = $this->filterElementByTagNameAndText('label', 'Start Date');
        if ($startDateElement) {
            $startDateElement->click();
        }

        try {
            $this->selectDateWithCustomId($startDate);
        } catch (\Exception $e) {

        }
    }

    /**
     * @param DateTime $endDate
     * @throws Exception
     */
    protected function setEndDate(DateTime $endDate)
    {
        sleep(1);

        $endDateElement = $this->filterElementByTagNameAndText('label', 'End Date');
        if ($endDateElement) {
            $endDateElement->click();
        }

        try {
            $this->selectDateWithCustomId($endDate);
        } catch (\Exception $e) {

        }
    }

    /**
     * @param DateTime $startDate
     * @param $id
     * @throws \Facebook\WebDriver\Exception\NoSuchElementException
     */
    private function selectDateWithCustomId($startDate, $id = null)
    {
        if ($this->firstDate instanceof DateTime) {
            $monthsBack = $this->diffMonths($startDate, $this->firstDate);
        } else {
            $this->firstDate = $startDate;
            $now = new DateTime('now');
            $monthsBack = $this->diffMonths($startDate, $now);
        }

        $clickMonthBack = $this->driver->findElement(WebDriverBy::cssSelector('button.ember-power-calendar-nav-control.ember-power-calendar-nav-control--previous'));
        while ($monthsBack > 0) {
            $clickMonthBack->click();
            $monthsBack--;
        }

        $monthElement = $this->driver->findElement(WebDriverBy::cssSelector('div.ember-power-calendar-days.ember-view'));
        $days = $monthElement->findElements(WebDriverBy::tagName('button'));
        $d = $startDate->format('j');
        foreach ($days as $day) {
            if ($d == $day->getText()) {
                $day->click();
                break;
            }
        }
    }

    /**
     * calculate diff months from startDate to endDate
     *
     * @param DateTime $startDate
     * @param DateTime $endDate
     * @return int positive value if endDate greater than startDate and vice verse
     */
    private function diffMonths(DateTime $startDate, DateTime $endDate)
    {
        /*
         * DO NOT USE $diffMonths = $this->firstDate->diff($startDate)->format('%m');
         * e.g:
         *   2017-11-28->diff(2017-12-30): diff-months=1, expected=1 OK because of diffDay=32 >= 30
         *   2017-11-28->diff(2017-12-26): diff-months=0, expected=1 FAILED because of diffDay=28 < 30
         *   2017-12-10->diff(2018-01-10): diff-months=1, expected=1 OK because of diffDay=30 >= 30
         *   2017-12-30->diff(2018-01-02): diff-months=1, expected=1 FAILED because of diffDay=28 < 30
         */
        $monthStartDate = (int)($startDate->format('Y')) * 12 + (int)($startDate->format('m'));
        $monthEndDate = (int)($endDate->format('Y')) * 12 + (int)($endDate->format('m'));

        return ($monthEndDate - $monthStartDate);
    }
}