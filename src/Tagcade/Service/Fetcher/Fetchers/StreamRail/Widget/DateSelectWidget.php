<?php

namespace Tagcade\Service\Fetcher\Fetchers\StreamRail\Widget;

use DateTime;
use Exception;
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
        $buttonClose = $this->filterElementByTagNameAndText('button', 'Close');
        if ($buttonClose) {
            $controls = $buttonClose->getAttribute('aria-controls');
            $id = substr($controls, 5, 4);
        }

        $yearElement = $this->driver->findElement(WebDriverBy::cssSelector(sprintf('#ember%s-input_root > div > div > div > div > div.picker__calendar-container > div > select.picker__select--year.browser-default', $id)));
        $yearSelect = new WebDriverSelect($yearElement);
        $y = $startDate->format("Y");
        $yearSelect->selectByValue($y);
        sleep(1);

        $monthElement = $this->driver->findElement(WebDriverBy::cssSelector(sprintf('#ember%s-input_root > div > div > div > div > div.picker__calendar-container > div > select.picker__select--month.browser-default', $id)));
        $monthSelect = new WebDriverSelect($monthElement);
        $m = $startDate->format("n");
        $monthSelect->selectByValue($m - 1);
        sleep(1);

        $tableElement = $this->driver->findElement(WebDriverBy::id(sprintf('ember%s-input_table', $id)));
        $dayElements = $tableElement->findElements(WebDriverBy::tagName('td'));
        $d = $startDate->format("j");
        sleep(1);

        foreach ($dayElements as $liElement) {
            if ($liElement->getText() == $d) {
                $liElement->click();
                break;
            }
        }

        sleep(1);

        $buttonClose = $this->filterElementByTagNameAndText('button', 'Close');
        if ($buttonClose) {
            $buttonClose->click();
        }
    }
}