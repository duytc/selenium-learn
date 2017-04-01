<?php

namespace Tagcade\Service\Fetcher\Fetchers\Streamrail\Widget;

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
        $this->setEndDate($startDate, $endDate);

        return $this;
    }

    /**
     * @param DateTime $startDate
     */
    protected function setStartDate(DateTime $startDate)
    {
        sleep(1);

        $startDateElement = $this->driver->findElement(WebDriverBy::id('ember1419-input'));
        $startDateElement->click();

        $yearElement = $this->driver->findElement(WebDriverBy::cssSelector('#ember1419-input_root > div > div > div > div > div.picker__calendar-container > div > select.picker__select--year.browser-default'));
        $yearSelect = new WebDriverSelect($yearElement);
        $y = $startDate->format("Y");
        $yearSelect->selectByValue($y);
        sleep(1);

        $monthElement = $this->driver->findElement(WebDriverBy::cssSelector('#ember1419-input_root > div > div > div > div > div.picker__calendar-container > div > select.picker__select--month.browser-default'));
        $monthSelect = new WebDriverSelect($monthElement);
        $m = $startDate->format("n");
        $monthSelect->selectByValue($m - 1);
        sleep(1);

        $tableElement = $this->driver->findElement(WebDriverBy::id('ember1419-input_table'));
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

        $buttonClose = $this->driver->findElement(WebDriverBy::cssSelector('#ember1419-input_root > div > div > div > div > div.picker__footer > button.btn-flat.picker__close'));
        $buttonClose->click();
    }

    /**
     * @param DateTime $endDate
     * @throws \Exception
     */
    protected function setEndDate(DateTime $endDate)
    {
        sleep(1);

        $this->driver->findElement(WebDriverBy::id('ember1420-input'))->click();

        $yearElement = $this->driver->findElement(WebDriverBy::cssSelector('#ember1420-input_root > div > div > div > div > div.picker__calendar-container > div > select.picker__select--year.browser-default'));
        $yearSelect = new WebDriverSelect($yearElement);
        $y = $endDate->format("Y");
        $yearSelect->selectByValue($y);
        sleep(1);

        $monthElement = $this->driver->findElement(WebDriverBy::cssSelector('#ember1420-input_root > div > div > div > div > div.picker__calendar-container > div > select.picker__select--month.browser-default'));
        $monthSelect = new WebDriverSelect($monthElement);
        $m = $endDate->format("n");
        $monthSelect->selectByValue($m - 1);
        sleep(1);

        $tableElement = $this->driver->findElement(WebDriverBy::id('ember1420-input_table'));
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
            $buttonClose = $this->driver->findElement(WebDriverBy::cssSelector('#ember1420-input_root > div > div > div > div > div.picker__footer > button.btn-flat.picker__close'));
            $buttonClose->click();
        } catch (\Exception $exp) {

        }

        sleep(1);
    }
}