<?php


namespace Tagcade\DataSource\NativeAds\Widget;


use DateTime;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Facebook\WebDriver\WebDriverSelect;

class DateSelectWidget extends AbstractWidget {

    const OPTION_CHOOSE_VALUE = 'Last 7 Days';
    /**
     * @param RemoteWebDriver $driver
     */
    public function __construct(RemoteWebDriver $driver)
    {
        parent::__construct($driver);
    }

    /**
     * @param DateTime $downloadDate
     * @internal param DateTime $startDate
     * @internal param DateTime $endDate
     * @return $this
     */
    public function setDate(DateTime $downloadDate)
    {
        $this->setStartDate($downloadDate);
        $this->setEndDate($downloadDate);

        $this->driver->wait()->until(WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::cssSelector('button[class="applyBtn btn btn-default btn-small btn-primary"]')));
        $this->driver->findElement(WebDriverBy::cssSelector('button[class="applyBtn btn btn-default btn-small btn-primary"]'))->click();

        $this->driver->wait()->until(WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::id('datatable_tabletools_processing')));
        $this->driver->wait()->until(WebDriverExpectedCondition::invisibilityOfElementLocated(WebDriverBy::id('datatable_tabletools_processing')));

    }

    /**
     * @param DateTime $startDate
     * @return $this
     * @throws \Exception
     * @throws \Facebook\WebDriver\Exception\NoSuchElementException
     * @throws \Facebook\WebDriver\Exception\TimeOutException
     * @throws null
     */
    protected function setStartDate(DateTime $startDate )
    {
        $year = $startDate->format('Y');
        $month = $startDate->format('F');
        $day = $startDate->format('d');

        $selectElement =  $this->driver->findElement(WebDriverBy::id('reportrange2'));
        $selectElement->click();

        $customElement =  $this->driver->findElement(
            WebDriverBy::cssSelector('body > div.daterangepicker.dropdown-menu.opensright > div.ranges > ul > li:nth-child(7)')
        );
        $customElement->click();

        $this->driver->wait()->until(
            WebDriverExpectedCondition::visibilityOfElementLocated (
                WebDriverBy::cssSelector('body > div.daterangepicker.dropdown-menu.opensright > div.calendar.left > div > table > thead > tr:nth-child(1) > th.month > select.monthselect'
                )
            )
        );

        $yearFromElement =  new WebDriverSelect (
            $this->driver->findElement(
                WebDriverBy::cssSelector(
                    'body > div.daterangepicker.dropdown-menu.opensright > div.calendar.left > div > table > thead > tr:nth-child(1) > th.month > select.yearselect')
            ));
        $yearFromElement->selectByValue($year);

        $monthFromElement =  new WebDriverSelect (
            $this->driver->findElement(
                WebDriverBy::cssSelector(
                    'body > div.daterangepicker.dropdown-menu.opensright > div.calendar.left > div > table > thead > tr:nth-child(1) > th.month > select.monthselect')
            ));
        $monthFromElement->selectByVisibleText($month);
        $this->driver->wait()->until(
            WebDriverExpectedCondition::visibilityOfElementLocated (
                WebDriverBy::xpath('/html/body/div[6]/div[2]/div/table/tbody/tr/td'
                )
            )
        );

        $weekElements = $this->driver->findElements(
            WebDriverBy::cssSelector(
                'body > div.daterangepicker.dropdown-menu.opensright > div.calendar.left > div > table > tbody > tr'
            ));

        $found = false;

        foreach($weekElements as $weekKey => $weekElement) {
            if(true == $found) {
                break;
            }

            $dateElements = $weekElement->findElements(WebDriverBy::cssSelector('td'));
            foreach ($dateElements as $key => $dateElement) {

                if ($dateElement->getText() == $day && $dateElement->getAttribute('class') !== 'week' && $dateElement->getAttribute('class') !=='available off') {

                    $cssValue = sprintf(
                        'body > div.daterangepicker.dropdown-menu.opensright > div.calendar.left > div > table > tbody > tr:nth-child(%d) > td:nth-child(%d)'
                        ,$weekKey+1, $key+1)
                    ;
                    $this->driver->findElement(WebDriverBy::cssSelector($cssValue))->click();

                    $found = true;
                    break;
                }
            }
        }

        return $this;
    }

    /**
     * @param DateTime $endDate
     * @return $this
     * @throws \Exception
     * @throws \Facebook\WebDriver\Exception\NoSuchElementException
     * @throws \Facebook\WebDriver\Exception\TimeOutException
     * @throws null
     */
    protected function setEndDate(DateTime $endDate)
    {
        $year = $endDate->format('Y');
        $month = $endDate->format('F');
        $day = $endDate->format('d');

        $yearFromElement =  new WebDriverSelect (
            $this->driver->findElement(
                WebDriverBy::cssSelector(
                    'body > div.daterangepicker.dropdown-menu.opensright > div.calendar.right > div > table > thead > tr:nth-child(1) > th.month > select.yearselect')
            ));
        $yearFromElement->selectByValue($year);

        $monthFromElement =  new WebDriverSelect (
            $this->driver->findElement(
                WebDriverBy::cssSelector(
                    'body > div.daterangepicker.dropdown-menu.opensright > div.calendar.right > div > table > thead > tr:nth-child(1) > th.month > select.monthselect')
            ));

        $monthFromElement->selectByVisibleText($month);
        $this->driver->wait()->until(WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::xpath('/html/body/div[6]/div[1]/div/table/tbody')));

        $weekElements = $this->driver->findElements(
            WebDriverBy::cssSelector(
                'body > div.daterangepicker.dropdown-menu.opensright > div.calendar.right > div > table > tbody > tr'
            ));

        $found = false;

        foreach ($weekElements as $weekKey => $weekElement) {
            if(true == $found) {
                break;
            }

            $dateElements = $weekElement->findElements(WebDriverBy::cssSelector('td'));
            foreach ($dateElements as $key => $dateElement) {
                if ($dateElement->getText() == $day && $dateElement->getAttribute('class') !== 'week' && $dateElement->getAttribute('class') !=='available off') {
                    $cssValue = sprintf(
                        'body > div.daterangepicker.dropdown-menu.opensright > div.calendar.right > div > table > tbody > tr:nth-child(%d) > td:nth-child(%d)',
                        $weekKey+1, $key+1);

                    $this->driver->findElement(WebDriverBy::cssSelector($cssValue))->click();
                    $found = true;

                    break;
                }
            }
        }

        return $this;
    }

} 