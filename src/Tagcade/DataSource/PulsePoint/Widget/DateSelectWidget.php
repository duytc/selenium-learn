<?php

namespace Tagcade\DataSource\PulsePoint\Widget;

use DateTime;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Facebook\WebDriver\WebDriverSelect;

class DateSelectWidget
{
    /**
     * @var RemoteWebDriver
     */
    private $driver;
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
        $this->driver = $driver;
        $this->fieldId = $fieldId;
    }

    public function setDate(DateTime $date)
    {
        $this->driver->findElement(WebDriverBy::cssSelector(sprintf('#%s + .datepick-trigger', $this->fieldId)))
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
        (new WebDriverSelect($yearSelectElement))
            ->selectByValue(static::getYearOption($date))
        ;

        $monthSelectElement = $this->driver->findElement(
            WebDriverBy::cssSelector('#datepick-div .datepick-new-month')
        );
        $this->driver->wait()->until(
            WebDriverExpectedCondition::visibilityOf($monthSelectElement)
        );
        (new WebDriverSelect($monthSelectElement))
            ->selectByValue(static::getMonthOption($date));

        $this->driver->wait()->until(
            WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::cssSelector('#datepick-div table.datepick tbody'))
        );
        $this->driver->findElement(WebDriverBy::linkText(static::getDayOption($date)))
            ->click()
        ;
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