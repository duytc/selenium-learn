<?php

namespace Tagcade\Service\Fetcher\Fetchers\Komoona\Page;

use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Tagcade\Service\Fetcher\Fetchers\Komoona\Widget\DateSelectWidget;
use Tagcade\Service\Fetcher\Pages\AbstractPage;

class IncomeReportPage extends AbstractPage
{
    const URL = 'https://www.komoona.com/reports/income';

    public function __construct(RemoteWebDriver $driver, $logger = null)
    {
        parent::__construct($driver);
        parent::setLogger($logger);
    }

    public function getAllTagReports(\DateTime $startDate, \DateTime $endDate = null)
    {
        // Step 1. Select date range
        $this->logger->debug('Selecting date range');
        $this->selectDateRange($startDate, $endDate);
        $this->driver->findElement(WebDriverBy::id('get-tags-reprot'))
            ->click();

        $this->driver->wait()->until(
            WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::id('tags-export-to-excel'))
        );

        $this->logger->debug('downloading excel report');

        $downloadElement = $this->driver->findElement(WebDriverBy::id('tags-export-to-excel'));
        $directoryStoreDownloadFile = $this->getDirectoryStoreDownloadFile($startDate, $endDate, $this->getConfig());
        $this->downloadThenWaitUntilComplete($downloadElement, $directoryStoreDownloadFile);
    }

    protected function selectDateRange(\DateTime $startDate, \DateTime $endDate = null)
    {
        if ($endDate == null) {
            $endDate = $startDate;
        }

        if ($startDate > $endDate) {
            throw new \InvalidArgumentException('The date range supplied is invalid');
        }

        $dateWidget = new DateSelectWidget($this->driver, $this->logger);
        $this->logger->debug(sprintf('Selecting start date %s', $startDate->format('Y-m-d')));
        $dateWidget->setDate($startDate, 'select#tags-date+input+img');
        $this->logger->debug(sprintf('Selecting end date %s', $endDate->format('Y-m-d')));
        $dateWidget->setDate($endDate, 'select#tags-date+input+img+input+img');

        return $this;
    }
}