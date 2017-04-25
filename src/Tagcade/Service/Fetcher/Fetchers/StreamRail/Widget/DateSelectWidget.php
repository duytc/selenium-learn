<?php

namespace Tagcade\Service\Fetcher\Fetchers\StreamRail\Widget;

use DateTime;
use Exception;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverSelect;
use Tagcade\Service\Fetcher\Fetchers\PulsePoint\Widget\AbstractWidget;

class DateSelectWidget extends AbstractWidget
{
    private $startDateElementIds = [null, 1419, 1957, 1597, 1980];

    /**
     * @param DateTime $startDate
     * @param DateTime $endDate
     * @return $this
     */
    public function setDateRange(DateTime $startDate, DateTime $endDate)
    {
        $pageSource = $this->driver->getPageSource();
        $posStartDate = strpos($pageSource, 'report-date input-field no-today no-clear sr-date-range--start no-margin no-error ember-view');
        $this->startDateElementIds[0] = (int) substr($pageSource, $posStartDate - 13, 4);

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

        $index = -1;
        do {
            try {
                $index++;
                $this->selectStartDateWithCustomId($startDate, $this->startDateElementIds[$index]);
                break;
            } catch (\Exception $e){

            }
        } while ($index < count($this->startDateElementIds));
    }

    /**
     * @param DateTime $endDate
     * @throws Exception
     */
    protected function setEndDate(DateTime $endDate)
    {
        sleep(1);
        $index = -1;

        do {
            try {
                $index++;
                $this->selectEndDateWithCustomId($endDate, $this->startDateElementIds[$index]);
                break;
            } catch (\Exception $e){

            }
        } while ($index < count($this->startDateElementIds));
    }

    /**
     * @param DateTime $startDate
     * @param $id
     * @throws \Facebook\WebDriver\Exception\NoSuchElementException
     */
    private function selectStartDateWithCustomId($startDate, $id){
        $startDateElement = $this->driver->findElement(WebDriverBy::id(sprintf('ember%s-input', $id)));
        $startDateElement->click();

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

        $buttonClose = $this->driver->findElement(WebDriverBy::cssSelector(sprintf('#ember%s-input_root > div > div > div > div > div.picker__footer > button.btn-flat.picker__close', $id)));
        $buttonClose->click();
    }

    /**
     * @param DateTime $endDate
     * @param $id
     * @throws \Facebook\WebDriver\Exception\NoSuchElementException
     */
    private function selectEndDateWithCustomId($endDate, $id){

        $this->driver->findElement(WebDriverBy::id(sprintf('ember%s-input', $id + 1)))->click();

        $yearElement = $this->driver->findElement(WebDriverBy::cssSelector(sprintf('#ember%s-input_root > div > div > div > div > div.picker__calendar-container > div > select.picker__select--year.browser-default', $id + 1)));
        $yearSelect = new WebDriverSelect($yearElement);
        $y = $endDate->format("Y");
        $yearSelect->selectByValue($y);
        sleep(1);

        $monthElement = $this->driver->findElement(WebDriverBy::cssSelector(sprintf('#ember%s-input_root > div > div > div > div > div.picker__calendar-container > div > select.picker__select--month.browser-default', $id + 1)));
        $monthSelect = new WebDriverSelect($monthElement);
        $m = $endDate->format("n");
        $monthSelect->selectByValue($m - 1);
        sleep(1);

        $tableElement = $this->driver->findElement(WebDriverBy::id(sprintf('ember%s-input_table', $id + 1)));
        $dayElements = $tableElement->findElements(WebDriverBy::tagName('td'));
        $d = $endDate->format("j");

        foreach ($dayElements as $liElement) {
            if ($liElement->getText() === $d) {
                $liElement->click();
                break;
            }
        }

        sleep(1);

        try {
            $buttonClose = $this->driver->findElement(WebDriverBy::cssSelector(sprintf('#ember%s-input_root > div > div > div > div > div.picker__footer > button.btn-flat.picker__close', $id + 1)));
            $buttonClose->click();
        } catch (Exception $exp) {

        }

        sleep(1);
    }
}