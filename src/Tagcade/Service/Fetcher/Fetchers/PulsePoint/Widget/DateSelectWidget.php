<?php

namespace Tagcade\Service\Fetcher\Fetchers\PulsePoint\Widget;

use DateTime;
use Facebook\WebDriver\Exception\NoSuchElementException;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Facebook\WebDriver\WebDriverSelect;
use Tagcade\Service\Fetcher\Fetchers\PulsePoint\Exception\InvalidDateRangeException;

class DateSelectWidget extends AbstractWidget
{
    /**
     * @var string
     */
    private $fieldId;

    /**
     * @param RemoteWebDriver $driver
     * @param string $fieldId
     */
    public function __construct(RemoteWebDriver $driver, $fieldId)
    {
        parent::__construct($driver);
        $this->fieldId = $fieldId;
    }

    /**
     * @param DateTime $date
     * @return $this
     * @throws \Exception
     * @throws \Facebook\WebDriver\Exception\NoSuchElementException
     * @throws \Facebook\WebDriver\Exception\TimeOutException
     */
    public function setDate(DateTime $date)
    {
        $dateButtonSel = WebDriverBy::cssSelector(sprintf('#%s + .datepick-trigger', $this->fieldId));

        $this->driver->wait()->until(WebDriverExpectedCondition::refreshed(
            WebDriverExpectedCondition::elementToBeClickable($dateButtonSel)
        ));

        $this->driver->findElement($dateButtonSel)
            ->click()
        ;

        $this->driver->wait()->until(
            WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::id('datepick-div'))
        );

        $yearSelectElement = $this->driver->findElement(
            WebDriverBy::cssSelector('#datepick-div .datepick-new-year')
        );
        $this->driver->wait()->until(
            WebDriverExpectedCondition::visibilityOf($yearSelectElement)
        );
        try {
            (new WebDriverSelect($yearSelectElement))
                ->selectByValue(static::getYearOption($date));
        } catch (NoSuchElementException $e) {
            throw new InvalidDateRangeException('Cannot select the years');
        }

        $monthSelectElement = $this->driver->findElement(
            WebDriverBy::cssSelector('#datepick-div .datepick-new-month')
        );
        $this->driver->wait()->until(
            WebDriverExpectedCondition::visibilityOf($monthSelectElement)
        );
        try {
            (new WebDriverSelect($monthSelectElement))
                ->selectByValue(static::getMonthOption($date));
        } catch (NoSuchElementException $e) {
            throw new InvalidDateRangeException('Cannot select the month');
        }

        $this->driver->wait()->until(
            WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::cssSelector('#datepick-div table.datepick tbody'))
        );
        try {
            $this->driver->findElement(WebDriverBy::linkText(static::getDayOption($date)))
                ->click()
            ;
        } catch (NoSuchElementException $e) {
            throw new InvalidDateRangeException('Cannot select the day');
        }

        return $this;
    }

    public function getDate()
    {
        $rawDate = $this->driver->findElement(WebDriverBy::id($this->fieldId))->getAttribute('value');
        return DateTime::createFromFormat('m/d/Y', $rawDate);
    }

    public static function getYearOption(Datetime $date)
    {
        return $date->format('Y');
    }

    public static function getMonthOption(DateTime $date)
    {
        $monthZeroIndex = (int) $date->format('n') - 1;
        return (string) $monthZeroIndex;
    }

    public static function getDayOption(DateTime $date)
    {
        return $date->format('j');
    }
}