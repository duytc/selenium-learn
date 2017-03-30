<?php


namespace Tagcade\Service\Fetcher\Fetchers\SpringServe\Widget;


use DateTime;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverElement;

class DateSelectWidget extends AbstractWidget
{

    const OPTION_SPECIFIC_VALUE = 'specific';

    /**
     * @param RemoteWebDriver $driver
     */
    public function __construct(RemoteWebDriver $driver)
    {
        parent::__construct($driver);
    }

    /**
     * @param DateTime $startDate
     * @param DateTime $endDate
     * @return $this
     */
    public function setDateRange(DateTime $startDate, DateTime $endDate)
    {
        /**@var WebDriverElement $dateRangeElement */
        $dateRangeElement = $this->driver->findElement(WebDriverBy::id('date_range_chosen'));
        $dateRangeElement->click();

        /**
         * @var WebDriverElement[] $liElements
         */
        $liElements = $dateRangeElement->findElements(WebDriverBy::tagName('li'));
        $needElement = null;

        foreach ($liElements as $liElement) {
            if ($liElement->getText() == 'Custom') {
                $needElement = $liElement;
                $needElement->click();

                /**@var WebDriverElement $customDateRange */
                $customDateRange = $this->driver->findElement(WebDriverBy::id('custom_date_range'));
                $customDateRange->click();

                $this->setStartDate($startDate);
                $this->setEndDate($endDate);

                /**
                 * @var WebDriverElement $applyButtons
                 */
                $applyButtons = $this->driver->findElement(WebDriverBy::className('range_inputs'));

                /**
                 * @var WebDriverElement[] $liElements
                 */
                $liElements = $applyButtons->findElements(WebDriverBy::tagName('button'));

                foreach ($liElements as $liElement) {
                    if ($liElement->getText() == 'Apply') {
                        $liElement->click();
                        break;
                    }
                }

                break;
            }
        }

        return $this;
    }

    /**
     * @param DateTime $startDate
     */
    protected function setStartDate(DateTime $startDate)
    {
        $startDate->setTime(0, 0);
        $this->driver->findElement(WebDriverBy::name('daterangepicker_start'))->clear()->sendKeys($startDate->format('m/d/y H:i'));
    }

    /**
     * @param DateTime $endDate
     */
    protected function setEndDate(DateTime $endDate)
    {
        $endDate->setTime(23, 59);
        $this->driver->findElement(WebDriverBy::name('daterangepicker_end'))->clear()->sendKeys($endDate->format('m/d/y H:i'));
    }
} 