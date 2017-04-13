<?php

namespace Tagcade\Service\Fetcher\Fetchers\DefyMedia\Page;

use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Tagcade\Service\Fetcher\Fetchers\DefyMedia\Widget\DateSelectWidget;
use Tagcade\Service\Fetcher\Pages\AbstractPage;

class ReportingPage extends AbstractPage
{
    const URL = 'https://pubportal.defymedia.com/app/report/publisher';

    public function getAllTagReports(\DateTime $startDate, \DateTime $endDate)
    {
        // step 0. select filter
        $this->driver->findElement(WebDriverBy::id('js-filter-row'))
            ->click();
        $this->driver->wait()->until(
            WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::id('report-date'))
        );

        // show report date selection
        $this->driver->findElement(WebDriverBy::id('report-date'))
            ->click();

        $this->driver->wait()->until(
            WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::cssSelector('.daterangepicker'))
        );

        // Step 1. Select date range
        $this->selectDateRange($startDate, $endDate);
        $downloadElement = $this->driver->findElement(WebDriverBy::id('report-export'));
        $directoryStoreDownloadFile = $this->getDirectoryStoreDownloadFile($startDate, $endDate, $this->getConfig());
        $this->downloadThenWaitUntilComplete($downloadElement, $directoryStoreDownloadFile);
        $this->logoutSystem();
    }

    protected function selectDateRange(\DateTime $startDate, \DateTime $endDate)
    {
        $dateWidget = new DateSelectWidget($this->driver);
        $dateWidget->setDateRange($startDate, $endDate);
        return $this;
    }

    protected function logoutSystem()
    {
        $this->logger->debug('Move mouse to logout area');
        $acountArea = 'body > div.navbar.navbar-inverse.navbar-fixed-top > div > div.navbar-tools > ul > li > a > span';
        $point = $this->driver->findElement(WebDriverBy::cssSelector($acountArea))->getCoordinates();
        $this->driver->getMouse()->mouseMove($point);

        $this->logger->debug('Click log out button');
        $logoutButtonCss = 'body > div.navbar.navbar-inverse.navbar-fixed-top > div > div.navbar-tools > ul > li > ul > li > a:nth-child(2) > i';
        $this->driver->findElement(WebDriverBy::cssSelector($logoutButtonCss))->click();
    }
}