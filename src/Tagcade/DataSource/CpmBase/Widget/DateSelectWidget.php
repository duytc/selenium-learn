<?php

namespace Tagcade\DataSource\CpmBase\Widget;

use DateTime;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Facebook\WebDriver\WebDriverSelect;
use Psr\Log\LoggerInterface;

class DateSelectWidget extends AbstractWidget
{

    const SPECIFY_DATE_RANGE_VALUE = 'specifydaterange';
    const NONE_DISPLAY_VALUE='none;';
    const NUM_DAYS_OF_WEEK = 7;
    const FIRST_BEAT_PICKER_INDEX = 3;
    const DATE_AVAIABLE = 'days-cell cell';

	/**
	 * @param RemoteWebDriver $driver
	 * @param LoggerInterface $logger
	 */
    public function __construct(RemoteWebDriver $driver, LoggerInterface $logger)
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
        $selectElement =  new WebDriverSelect ($this->driver->findElement(WebDriverBy::name('interval')));
        $selectedOptions = $selectElement->getOptions();
        $values = [];
        foreach($selectedOptions as $selectedOption) {
            $values[] =  $selectedOption->getAttribute('value');
        }

        foreach ($values as $value) {
            if ( 0 == strcmp($value, self::SPECIFY_DATE_RANGE_VALUE)) {
               $selectElement =  new WebDriverSelect ($this->driver->findElement(WebDriverBy::name('interval')));
               $selectElement->selectByValue($value);
               $this->driver->wait()->until(WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::name('intervaldatefrom')));

               $this->setStartDate($startDate);
               $this->setEndDate($endDate);
            }
        }

        return $this;
    }

    /**
     * @param DateTime $startDate
     */
    protected function setStartDate(DateTime $startDate )
    {
        $monthYearStart = $startDate->format('F Y');
        $day = $startDate->format('d');

        $this->driver->findElement(WebDriverBy::name('intervaldatefrom'))->click();

        /*Click to select month and year*/

        $monthYearElement = $this->driver->findElement(WebDriverBy::cssSelector('body > div:nth-child(6) > div.header > div > a.header-navbar-button.nav-btn.current-indicator.button'));
        $monthYearValue = $monthYearElement->getText();

        $clickToPrevious = $this->isPrevioustNavigator($monthYearValue, $monthYearStart);

        while (0 != strcmp($monthYearStart, $monthYearValue)) {

            if(true == $clickToPrevious) {
                $this->driver->findElement(WebDriverBy::cssSelector('body > div:nth-child(6) > div.header > div > a.header-navbar-button.nav-btn.prev.button'))->click();
            } else {
                $this->driver->findElement(WebDriverBy::cssSelector('body > div:nth-child(6) > div.header > div > a.header-navbar-button.nav-btn.next.button'))->click();
            }

            $monthYearElement = $this->driver->findElement(WebDriverBy::cssSelector('body > div:nth-child(6) > div.header > div > a.header-navbar-button.nav-btn.current-indicator.button'));
            $monthYearValue = $monthYearElement->getText();
        }

        $this->clickToPlainText();
        $this->driver->findElement(WebDriverBy::name('intervaldatefrom'))->click();


        $allBeatPickers = $this->driver->findElements(WebDriverBy::cssSelector('div[class="beatpicker beatpicker"]'));

        foreach($allBeatPickers as $month => $allBeatPicker) {
            $cssString = $allBeatPicker->getAttribute('style');

            if(false == strpos($cssString, self::NONE_DISPLAY_VALUE)) {

                $cssValue = sprintf('div[style="%s"]', $cssString);
                $beatPicker = $this->driver->findElement(WebDriverBy::cssSelector($cssValue));
                $days = $beatPicker->findElements(WebDriverBy::cssSelector('li'));

                foreach($days as $key => $day1) {

                    if($day1->getText() == $day && $day1->getAttribute('class') == self::DATE_AVAIABLE) {

                        $cssValue = sprintf('/html/body/div[%d]/div[2]/ul/li[%d]', $month+self::FIRST_BEAT_PICKER_INDEX, $key-self::NUM_DAYS_OF_WEEK+1);
                        $clickDay = $this->driver->findElement(WebDriverBy::xpath($cssValue));
                        $clickDay->click();
                        break;
                    }
                }
            }
        }

    }


    protected function clickToPlainText()
    {
        $this->driver->findElement(WebDriverBy::cssSelector('body > div.wrap > div.content > div > div.title'))->click();
    }


    /**
     * @param DateTime $endDate
     */
    protected function setEndDate(DateTime $endDate)
    {
        $monthYearStart = $endDate->format('F Y');
        $day = $endDate->format('d');

        $this->driver->findElement(WebDriverBy::name('intervaldateto'))->click();

        $allBeatPickers = $this->driver->findElements(WebDriverBy::cssSelector('div[class="beatpicker beatpicker"]'));

        $chooseBeatPicker = 0;
        foreach ($allBeatPickers as $month => $allBeatPicker) {

            $cssString = $allBeatPicker->getAttribute('style');
            if(false == strpos($cssString, self::NONE_DISPLAY_VALUE)) {
                $chooseBeatPicker = $month+6;
            }
        }

        $this->logger->info(sprintf('Choose beat picker = %d', $chooseBeatPicker));

        $cssMonthYearElement = sprintf('body > div:nth-child(%d) > div.header > div > a.header-navbar-button.nav-btn.current-indicator.button', $chooseBeatPicker);
        $monthYearElement = $this->driver->findElement(WebDriverBy::cssSelector($cssMonthYearElement));
        $monthYearValue = $monthYearElement->getText();

        $clickToPrevious = $this->isPrevioustNavigator($monthYearValue, $monthYearStart);

        while (0 != strcmp($monthYearStart, $monthYearValue)) {
            if(true == $clickToPrevious) {
                $preCssString = sprintf('body > div:nth-child(%d) > div.header > div > a.header-navbar-button.nav-btn.prev.button', $chooseBeatPicker);
                $this->driver->findElement(WebDriverBy::cssSelector($preCssString))->click();
            } else {
                $nextCssString = sprintf('body > div:nth-child(%d) > div.header > div > a.header-navbar-button.nav-btn.next.button', $chooseBeatPicker);
                $this->driver->findElement(WebDriverBy::cssSelector($nextCssString))->click();
            }

            $monthYearElement = $this->driver->findElement(WebDriverBy::cssSelector($cssMonthYearElement));
            $monthYearValue = $monthYearElement->getText();
        }

        $this->clickToPlainText();
        sleep(2);
        $this->driver->findElement(WebDriverBy::name('intervaldateto'))->click();


        $tests = $this->driver->findElements(WebDriverBy::cssSelector('div[class="beatpicker beatpicker"]'));

        foreach ($tests as $month => $test) {
            $cssString = $test->getAttribute('style');

            if(false == strpos($cssString, self::NONE_DISPLAY_VALUE)) {

                $cssValue = sprintf('div[style="%s"]', $cssString);
                $beatPicker = $this->driver->findElement(WebDriverBy::cssSelector($cssValue));
                $days = $beatPicker->findElements(WebDriverBy::cssSelector('li'));

                foreach($days as $key => $day1) {
                    if($day1->getText() == $day && $day1->getAttribute('class') == self::DATE_AVAIABLE) {

                        $cssValue = sprintf('/html/body/div[%d]/div[2]/ul/li[%d]', $month+self::FIRST_BEAT_PICKER_INDEX, $key-self::NUM_DAYS_OF_WEEK+1);
                        $clickDay = $this->driver->findElement(WebDriverBy::xpath($cssValue));
                        $clickDay->click();
                        break;
                    }
                }

            }

        }


    }

}