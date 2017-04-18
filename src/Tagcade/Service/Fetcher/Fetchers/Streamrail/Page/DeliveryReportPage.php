<?php

namespace Tagcade\Service\Fetcher\Fetchers\Streamrail\Page;

use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Tagcade\Service\Fetcher\Fetchers\Streamrail\Widget\DateSelectWidget;
use Tagcade\Service\Fetcher\Pages\AbstractPage;

class DeliveryReportPage extends AbstractPage
{
    public function getAllTagReports(\DateTime $startDate, \DateTime $endDate)
    {
        // step 0. select filter
        $this->logger->debug('select filter');
        $this->sleep(2);
        $this->driver->wait()->until(WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::id('ember1204')));

        // select date range
        $this->driver->findElement(WebDriverBy::id('ember-basic-dropdown-trigger-ember1247'))->click();
        $this->driver->findElement(WebDriverBy::cssSelector('#ember-power-select-options-ember1247 > li:nth-child(5)'))->click();

        $this->selectDateRange($startDate, $endDate);

        $this->sleep(2);

        $this->driver->findElement(WebDriverBy::xpath('//*[@data-tooltip="Apply Filters"]'))->click();

        $this->driver->wait()->until(WebDriverExpectedCondition::invisibilityOfElementLocated(WebDriverBy::xpath('//*[@data-tooltip="Undo"]')));

        $this->sleep(2);

        $downloadElement = $this->driver->findElement(WebDriverBy::xpath('//*[@data-tooltip="Export to CSV"]'));
        $directoryStoreDownloadFile = $this->getDirectoryStoreDownloadFile($startDate, $endDate, $this->getConfig());

        $this->downloadThenWaitUntilComplete($downloadElement, $directoryStoreDownloadFile);

        $this->logger->debug('Logout system');
        $this->logOutSystem();
    }

    protected function selectDateRange(\DateTime $startDate, \DateTime $endDate)
    {
        $now = new \DateTime();
        if ($now->format('Ymd') === $startDate->format('Ymd') || $now->format('Ymd') === $endDate->format('Ymd')) {
            throw new \Exception('not supported startDate or endDate equal today');
        }

        $dateWidget = new DateSelectWidget($this->driver);
        $dateWidget->setDateRange($startDate, $endDate);
        return $this;
    }

    protected function logOutSystem()
    {
        $this->driver->findElement(WebDriverBy::cssSelector('#ember1163'))->click();
        $this->sleep(3);
        $this->driver->findElement(WebDriverBy::id('ember616'))->click();
    }
}