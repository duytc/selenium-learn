<?php

namespace Tagcade\DataSource\Openx\Page;

use Facebook\WebDriver\Exception\TimeOutException;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Tagcade\DataSource\PulsePoint\Page\AbstractPage;

class ReportingPage extends AbstractPage
{
    const URL = 'http://us-market.openx.com/#/reports?tab=my_reports';

    public function getAllTagReports(\DateTime $startDate, \DateTime $endDate)
    {
        $this->driver->wait()->until(
            WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::tagName("iframe"))
        );

        $iframe = $this->driver->switchTo()->frame($this->driver->findElement(WebDriverBy::tagName("iframe")));

        $this->driver->wait()->until(
            WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::xpath("//a[text()[contains(.,'Exchange Revenue_Yesterday')]]"))
        );

        $iframe->findElement(WebDriverBy::xpath("//a[text()[contains(.,'Exchange Revenue_Yesterday')]]"))->click();

        try {
            $this->driver->manage()->timeouts()->setScriptTimeout(200);
            $iframePopup = $iframe->switchTo()->frame($iframe->findElement(WebDriverBy::cssSelector("#popupFrame > iframe")));
            $iframePopup->wait()->until(
                WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::cssSelector("#exportDropdown"))
            );

            $iframePopup->findElement(WebDriverBy::cssSelector("#exportDropdown > section > div"))->click();
            $downloadElement = $iframePopup->findElement(WebDriverBy::cssSelector("#exportDropdown > section > div > #dd > ul > li:nth-child(1) > a"));

            $directoryStoreDownloadFile = $this->getDirectoryStoreDownloadFile($startDate, $endDate, $this->getConfig());
            $this->downloadThenWaitUntilComplete($downloadElement, $directoryStoreDownloadFile);
        } catch (TimeOutException $te) {
            $this->logger->error('No data available for selected date range.');
        } catch (\Exception $exception) {
            $this->logger->error($exception->getMessage());
        }
    }
}