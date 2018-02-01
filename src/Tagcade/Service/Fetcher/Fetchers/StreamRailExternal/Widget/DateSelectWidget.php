<?php

namespace Tagcade\Service\Fetcher\Fetchers\StreamRailExternal\Widget;

use DateTime;
use Exception;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverSelect;
use Symfony\Component\Validator\Constraints\Date;
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
            // $monthsBack = $this->firstDate->diff($startDate)->format('%m'); // wrong diff, TODO: remove
            $monthsBack = $this->firstDate->format('n') - $startDate->format('n');
        }
        else {
            $this->firstDate = $startDate;
            $now = new DateTime('now');
            // $monthsBack = $this->firstDate->diff($startDate)->format('%m'); // wrong diff, TODO: remove
            $monthsBack = $now->format('n') - $startDate->format('n');
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
}