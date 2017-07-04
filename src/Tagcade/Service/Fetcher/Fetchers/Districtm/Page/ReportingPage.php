<?php

namespace Tagcade\Service\Fetcher\Fetchers\Districtm\Page;

use Exception;
use Facebook\WebDriver\Exception\TimeOutException;
use Facebook\WebDriver\Remote\RemoteWebElement;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Tagcade\Service\Fetcher\Fetchers\Districtm\Widget\DateSelectWidget;
use Tagcade\Service\Fetcher\Pages\AbstractPage;

class ReportingPage extends AbstractPage
{
    const URL = 'http://b3.districtm.ca/metrics/loadReport/byDayByDomainByAdSize';

    public function getAllTagReports(\DateTime $startDate, \DateTime $endDate)
    {
        $this->driver->wait()->until(
            WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::id('downloadToCSVButton'))
        );

        $this->selectDateRange($startDate, $endDate);

        try {
            /** @var RemoteWebElement $downloadBtn */
            $downloadElement = $this->driver->findElement(WebDriverBy::id("downloadToCSVButton"));

            $directoryStoreDownloadFile = $this->getDirectoryStoreDownloadFile($startDate, $endDate, $this->getConfig());
            $this->downloadThenWaitUntilComplete($downloadElement, $directoryStoreDownloadFile);

        } catch (TimeOutException $te) {
            $this->logger->notice('No data available for selected date range.');
        } catch (Exception $exception) {
            $this->logger->notice($exception->getMessage());
        }
    }

    /**
     * @param \DateTime $startDate
     * @param \DateTime $endDate
     * @return $this
     */
    protected function selectDateRange(\DateTime $startDate, \DateTime $endDate)
    {
        $dateWidget = new DateSelectWidget($this->driver, $this->logger);
        $dateWidget->setDateRange($startDate, $endDate);
        return $this;
    }
}