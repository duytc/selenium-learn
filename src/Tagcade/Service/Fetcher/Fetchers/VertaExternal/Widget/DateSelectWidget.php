<?php


namespace Tagcade\Service\Fetcher\Fetchers\VertaExternal\Widget;


use DateTime;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Remote\RemoteWebElement;
use Facebook\WebDriver\WebDriverBy;
use Tagcade\Service\Fetcher\Fetchers\VertaExternal\Util\VertaExternalUtil;

class DateSelectWidget extends AbstractWidget
{
    private $specialId;

    /**
     * @param RemoteWebDriver $driver
     * @param \Monolog\Logger $specialId
     */
    public function __construct(RemoteWebDriver $driver, $specialId)
    {
        parent::__construct($driver);
        $this->specialId = $specialId;
    }

    /**
     * @param DateTime $startDate
     * @param DateTime $endDate
     * @return $this
     */
    public function setDateRange(DateTime $startDate, DateTime $endDate)
    {
        /** Can not send keys to custom date, so select date/month/year is hard and more complicated*/
        $this->selectCustomDate();
        $this->setStartDate($startDate);
        $this->setEndDate($endDate);

        $useDateBtn = VertaExternalUtil::getId(VertaExternalUtil::BUTTON, $this->specialId + 241);
        $this->driver->findElement(WebDriverBy::id($useDateBtn))->click();
    }

    /**
     * @param DateTime $startDate
     */
    protected function setStartDate(DateTime $startDate)
    {
        $selectMonthYear = $this->specialId + 243;
        $this->driver->findElement(WebDriverBy::id(VertaExternalUtil::getId(VertaExternalUtil::BUTTON_SPLIT, $selectMonthYear)))->click();

        $this->selectYear($startDate, $this->specialId + 247);
        $this->selectMonth($startDate, $this->specialId + 247);

        $okBtn = $this->specialId + 248;
        $this->driver->findElement(WebDriverBy::id(VertaExternalUtil::getId(VertaExternalUtil::BUTTON, $okBtn)))->click();

        $this->selectDate($startDate, $this->specialId + 238);
    }

    /**
     * @param DateTime $endDate
     */
    protected function setEndDate(DateTime $endDate)
    {
        $selectMonthYear = $this->specialId + 245;
        $this->driver->findElement(WebDriverBy::id(VertaExternalUtil::getId(VertaExternalUtil::BUTTON_SPLIT, $selectMonthYear)))->click();

        $this->selectYear($endDate, $this->specialId + 250);
        $this->selectMonth($endDate, $this->specialId + 250);

        $okBtn = $this->specialId + 251;
        $this->driver->findElement(WebDriverBy::id(VertaExternalUtil::getId(VertaExternalUtil::BUTTON, $okBtn)))->click();

        $this->selectDate($endDate, $this->specialId + 239);
    }

    private function selectCustomDate()
    {
        $selectCustomDate = VertaExternalUtil::getId(VertaExternalUtil::BUTTON, $this->specialId + 13);
        $customDateBtn = $this->driver->findElement(WebDriverBy::id($selectCustomDate));
        $customDateBtn->click();
    }

    /**
     * @param DateTime $date
     * @param $id
     */
    private function selectYear($date, $id)
    {
        $yearTable = $this->driver->findElement(WebDriverBy::id(VertaExternalUtil::getId(VertaExternalUtil::YEAR_PICKER, $id)));
        $years = $yearTable->findElements(WebDriverBy::tagName('a'));

        $dateYear = $date->format('Y');

        foreach ($years as $year) {
            if (!$year instanceof RemoteWebElement) {
                continue;
            }
            if ($year->getText() == $dateYear) {
                $year->click();
                return;
            }
        }
    }

    /**
     * @param DateTime $date
     * @param $id
     */
    private function selectMonth($date, $id)
    {
        $monthTable = $this->driver->findElement(WebDriverBy::id(VertaExternalUtil::getId(VertaExternalUtil::MONTH_PICKER, $id)));
        $months = $monthTable->findElements(WebDriverBy::tagName('a'));

        $dateMonth = $date->format('M');

        foreach ($months as $month) {
            if (!$month instanceof RemoteWebElement) {
                continue;
            }
            if ($month->getText() == $dateMonth) {
                $month->click();
                return;
            }
        }
    }

    /**
     * @param DateTime $date
     * @param $id
     */
    private function selectDate($date, $id)
    {
        $dateTable = $this->driver->findElement(WebDriverBy::id(VertaExternalUtil::getId(VertaExternalUtil::CUSTOM_DATE_PICKER, $id)));
        $dates = $dateTable->findElements(WebDriverBy::tagName('td'));

        $dateValue = $date->format('d');

        foreach ($dates as $date) {
            if (!$date instanceof RemoteWebElement) {
                continue;
            }
            if (!($date->getAttribute('class') == 'x-datepicker-active x-datepicker-cell' ||
                $date->getAttribute('class') == 'x-datepicker-active x-datepicker-cell x-datepicker-selected')) {
                continue;
            }
            if ($date->getText() == $dateValue) {
                $date->click();
                return;
            }
        }
    }
}