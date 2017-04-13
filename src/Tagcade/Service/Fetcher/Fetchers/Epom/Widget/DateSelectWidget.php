<?php


namespace Tagcade\Service\Fetcher\Fetchers\Epom\Widget;


use DateTime;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;
use Psr\Log\LoggerInterface;
use Tagcade\Service\Fetcher\Fetchers\PulsePoint\Widget\AbstractWidget;

class DateSelectWidget extends AbstractWidget {

    const CUSTOM_RANGE_VALUE_INDEX          = 11;
    const KICK_DOWN_SITE_INDEX              = 1;

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
        $this->logger->debug('Go to set date range');
        $dateRangeId = 'analytics-range-triggerWrap';
        $tableDateRange = $this->driver->findElement(WebDriverBy::id($dateRangeId));

        $this->logger->debug('Pull down date range option');
        $trElements = $tableDateRange->findElements(WebDriverBy::cssSelector('td'));
        $trElements[self::KICK_DOWN_SITE_INDEX]->click();

        $this->logger->debug('Choose date range custom range option');
        $listOptionsUlElement = $this->driver->findElement(WebDriverBy::cssSelector('ul[class="x-list-plain"]'));
        $liOptions = $listOptionsUlElement->findElements(WebDriverBy::cssSelector('li'));
        $liOptions[self::CUSTOM_RANGE_VALUE_INDEX]->click();

        $this->setStartDate($startDate);
        $this->setEndDate($endDate);

        return $this;
    }

    /**
     * @param DateTime $startDate
     */
    protected function setStartDate(DateTime $startDate )
    {
        $this->driver->findElement(WebDriverBy::id('analytics-customFrom-inputEl'))->clear()->sendKeys($startDate->format('d/m/Y'));
    }

    /**
     * @param DateTime $endDate
     */
    protected function setEndDate(DateTime $endDate)
    {
        $this->driver->findElement(WebDriverBy::id('analytics-customTo-inputEl'))->clear()->sendKeys($endDate->format('d/m/Y'));
    }

} 