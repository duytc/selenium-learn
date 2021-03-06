<?php

namespace Tagcade\DataSource\Technorati\Page;

use Facebook\WebDriver\Exception\TimeOutException;
use Facebook\WebDriver\Remote\RemoteWebElement;
use Facebook\WebDriver\WebDriverBy;
use Tagcade\DataSource\PulsePoint\Page\AbstractPage;

class ReportingPage extends AbstractPage
{
    const URL = 'https://mycontango.technorati.com/#/reports';

    public function getAllTagReports(\DateTime $startDate, \DateTime $endDate)
    {
        $this->logger->info('get all tag');

        sleep(5);

        $this->driver->findElement(WebDriverBy::cssSelector('div[ng-click="report.group_by_site = !report.group_by_site"]'))->click();
        $this->driver->findElement(WebDriverBy::cssSelector('div[ng-click="report.group_by_section = !report.group_by_section"]'))->click();
        $this->driver->findElement(WebDriverBy::cssSelector('div[ng-click="report.group_by_ad_size = !report.group_by_ad_size"]'))->click();
        $this->driver->findElement(WebDriverBy::cssSelector('a[ng-click="run()"]'))->click();

        try {
            /** @var RemoteWebElement $downloadBtn */
            $downloadElement = $this->driver->findElement(WebDriverBy::cssSelector('a[ng-click="download()"]'));

            $directoryStoreDownloadFile = $this->getDirectoryStoreDownloadFile($startDate, $endDate, $this->getConfig());
            $this->downloadThenWaitUntilComplete($downloadElement, $directoryStoreDownloadFile);
            $this->logoutSystem();

        } catch (TimeOutException $te) {
            $this->logger->error('No data available for selected date range.');
        } catch (\Exception $exception) {
            $this->logger->error($exception->getMessage());
        }
    }

    protected function logoutSystem()
    {
        $logoutAreaCss = '#usernav > div > span.name.ng-binding';
        $this->driver->findElement(WebDriverBy::cssSelector($logoutAreaCss))->click();

        $this->driver->findElement(WebDriverBy::cssSelector('a[ng-click="logout()"]'))->click();
    }
}