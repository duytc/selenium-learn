<?php

namespace Tagcade\Service\Fetcher\Fetchers\Komoona\Widget;

use DateTime;
use Exception;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Monolog\Logger;

class DateSelectWidget extends AbstractWidget
{
    /**
     * @var string
     */
    private $fieldId;

    /**
     * @param RemoteWebDriver $driver
     * @param Logger $logger
     */
    public function __construct(RemoteWebDriver $driver, Logger $logger =null)
    {
        parent::__construct($driver, $logger);

    }

    /**
     * @param DateTime $date
     * @param string $cssSelectorForCalendarActivationButton
     * @return $this
     * @throws \Exception
     * @throws \Facebook\WebDriver\Exception\NoSuchElementException
     * @throws \Facebook\WebDriver\Exception\TimeOutException
     */
    public function setDate(DateTime $date, $cssSelectorForCalendarActivationButton)
    {
        $this->driver->findElement(WebDriverBy::cssSelector($cssSelectorForCalendarActivationButton))
            ->click()
        ;

        $this->driver->wait()->until(
            WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::cssSelector('.ui-datepicker-year'))
        );

        $currentYear = $this->driver->findElement(WebDriverBy::cssSelector('.ui-datepicker-year'))
            ->getText()
        ;
        $expectYear = $date->format('Y');
        $yearCounter = $currentYear != $expectYear ? ($currentYear > $expectYear ? - 1 : 1) : 0;
        while ($currentYear != $expectYear) {
            $cssSelect = $yearCounter < 0 ? '.ui-datepicker-prev' : '.ui-datepicker-next';
            $this->driver->findElement(WebDriverBy::cssSelector($cssSelect))
                ->click()
            ;

            usleep(50);
            $currentYear = $this->driver->findElement(WebDriverBy::cssSelector('.ui-datepicker-year'))
                ->getText()
            ;
            usleep(50);
        }

        $currentMonth = $this->driver->findElement(WebDriverBy::cssSelector('.ui-datepicker-month'))
            ->getText()
        ;
        $currentMonth = (int)date('n', strtotime($currentMonth));
        $expectMonth = (int)$date->format('m');
        $monthCounter = $currentMonth != $expectMonth ? ($currentMonth > $expectMonth ? - 1 : 1) : 0;
        while ($currentMonth != $expectMonth) {
            $cssSelect = $monthCounter < 0 ? '.ui-datepicker-prev' : '.ui-datepicker-next';
            $this->driver->findElement(WebDriverBy::cssSelector($cssSelect))
                ->click()
            ;
            usleep(50);
            $currentMonth = $this->driver->findElement(WebDriverBy::cssSelector('.ui-datepicker-month'))
                ->getText()
            ;
            $currentMonth = (int)date('n', strtotime($currentMonth));
            usleep(50);
        }

        // Select date
        $expectDate = (int)$date->format('d');
        try {
            $this->driver->findElement(WebDriverBy::linkText((string)$expectDate))->click();
        } catch (Exception $e) {
            $this->logger->error(sprintf('Exception when set date =%s, exception meassage =%s',$date->format('Y-m-d'),$e->getMessage()));
            throw $e;
        }

        return $this;
    }

    public function getDate()
    {
        $rawDate = $this->driver->findElement(WebDriverBy::id($this->fieldId))->getAttribute('value');
        return DateTime::createFromFormat('m/d/Y', $rawDate);
    }

    public static function getYearOption(DateTime $date)
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