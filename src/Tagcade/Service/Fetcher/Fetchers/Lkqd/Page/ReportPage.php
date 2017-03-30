<?php


namespace Tagcade\Service\Fetcher\Fetchers\Lkqd\Page;


use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Tagcade\Service\Fetcher\Fetchers\Lkqd\Widget\DateSelectWidget;
use Tagcade\Service\Fetcher\Fetchers\Lkqd\Widget\ReportSourceSelectWidget;
use Tagcade\Service\Fetcher\Fetchers\Lkqd\Widget\ReportTypeSelectWidget;
use Tagcade\Service\Fetcher\Fetchers\PulsePoint\Page\AbstractPage;

class ReportPage extends AbstractPage
{
    const URL = 'https://ui.lkqd.com/reports';
    const DAILY_REPORT_INDEX = 3;
    const SUPPLY_SOURCE_INDEX = 2;
    const DOWNLOAD_BUTTON_INDEX = 5;

    public function getAllTagReports(\DateTime $startDate, \DateTime $endDate)
    {
        $this->selectReportSource();
        $this->selectReportType();
        $this->selectDateRange($startDate, $endDate);

        /** RemoveWebDriver $downloadElement */
        $downloadElement = $this->driver->findElement(WebDriverBy::xpath('//div[@class="row-4"]/button[contains(@class, "download-button")]'));
        $this->driver->wait()->until(WebDriverExpectedCondition::elementToBeClickable(WebDriverBy::xpath('//div[@class="row-4"]/button[contains(@class, "download-button")]')));
        $directoryStoreDownloadFile = $this->getDirectoryStoreDownloadFile($startDate, $endDate, $this->getConfig());
        $this->downloadThenWaitUntilComplete($downloadElement, $directoryStoreDownloadFile);

        $this->logger->debug('Logout system');
        $this->logOutSystem();
    }

    protected function selectDateRange(\DateTime $startDate, \DateTime $endDate)
    {
        $dateWidget = new DateSelectWidget($this->driver, $this->logger);
        $dateWidget->setDateRange($startDate, $endDate);
        return $this;
    }

    protected function selectReportType()
    {
        $dateWidget = new ReportTypeSelectWidget($this->driver, $this->logger);
        $dateWidget->setReportType(self::DAILY_REPORT_INDEX);
        return $this;
    }

    protected function selectReportSource()
    {
        $dateWidget = new ReportSourceSelectWidget($this->driver, $this->logger);
        $dateWidget->setReportSource(self::SUPPLY_SOURCE_INDEX);
        return $this;
    }

    protected function logOutSystem()
    {
        $this->driver->findElement(WebDriverBy::xpath('//ul[contains(@class, "navbar-right")]/li[contains(@class, "navigation-bar-item")]/div/a[contains(@class, "caret-button")]/span'))->click();
        $this->driver->findElement(WebDriverBy::xpath('//ul[contains(@class, "account-dropdown-items") and contains(@class, "dropdown-menu")]/li[4]'))->click();
        return $this;
    }
}