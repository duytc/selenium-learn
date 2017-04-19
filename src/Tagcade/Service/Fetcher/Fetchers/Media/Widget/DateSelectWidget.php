<?php

namespace Tagcade\Service\Fetcher\Fetchers\Media\Widget;

use DateTime;
use Exception;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;
use Monolog\Logger;

class DateSelectWidget extends AbstractWidget
{
    /**
     * @param RemoteWebDriver $driver
     * @param Logger $logger
     */
    public function __construct(RemoteWebDriver $driver, Logger $logger = null)
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
        sleep(2);
        $this->setStartDate($startDate);
        sleep(2);
        $this->setEndDate($endDate);

        return $this;
    }

    /**
     * @param DateTime $startDate
     * @throws Exception
     */
    protected function setStartDate(DateTime $startDate)
    {
        try {
            $this->logger->debug('Starting set start date');
            $this->driver->findElement(WebDriverBy::id('from'))->clear()->sendKeys($startDate->format('m/d/y'));
        } catch (Exception $e) {
            $this->logger->error(sprintf('Can not set start date =%d', $startDate->format('Y-m-d')));
            throw $e;
        }
    }

    /**
     * @param DateTime $endDate
     * @throws Exception
     */
    protected function setEndDate(DateTime $endDate)
    {
        try {
            $this->logger->debug('Starting set end date');
            $this->driver->findElement(WebDriverBy::id('to'))->clear()->sendKeys($endDate->format('m/d/y'));
        } catch (Exception $e) {
            $this->logger->error(sprintf('Can not set end date =%d', $endDate->format('Y-m-d')));
            throw $e;
        }
    }

}