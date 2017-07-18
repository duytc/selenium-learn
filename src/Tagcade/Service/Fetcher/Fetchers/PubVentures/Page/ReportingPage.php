<?php

namespace Tagcade\Service\Fetcher\Fetchers\PubVentures\Page;

use Exception;
use Facebook\WebDriver\Exception\TimeOutException;
use Facebook\WebDriver\Remote\RemoteWebElement;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Tagcade\Service\Fetcher\Fetchers\PubVentures\Widget\DateSelectWidget;
use Tagcade\Service\Fetcher\Pages\AbstractPage;

class ReportingPage extends AbstractPage
{
    const URL = 'http://ui.pubventuresmedia.com/report-v2/publisher/run-report/16';
    const CLASS_RANGE = '#app > div > div.report-container > main > form > fieldset.report-settings-metrics.report-form-col > fieldset.report-settings > fieldset > div:nth-child(1) > div > span > div > div > span';
    const CLASS_INTERVAL = "#app > div > div.report-container > main > form > fieldset.report-settings-metrics.report-form-col > fieldset.report-settings > fieldset > div:nth-child(3) > div > span > div > div > span";
    const CLASS_TIMEZONE = "#app > div > div.report-container > main > form > fieldset.report-settings-metrics.report-form-col > fieldset.report-settings > fieldset > div:nth-child(4) > div > span > div > div > span";
    const CLASS_PLACEMENT = "#app > div > div.report-container > main > form > fieldset.report-dimensions.report-form-col > div > ul.report-dimensions-list > li:nth-child(7) > label";

    public function getAllTagReports(\DateTime $startDate, \DateTime $endDate)
    {
        $this->driver->wait()->until(
            WebDriverExpectedCondition::invisibilityOfElementLocated(WebDriverBy::cssSelector('ArLoadingOverlay-indicator-progress-bar'))
        );

        // step 0. Report tab
        $this->selectRange();
        $this->selectDateRange($startDate, $endDate);
        $this->selectInterval();
        $this->selectTimezone();
        $this->selectPlacement();

        // step 1. run report
        $this->driver->findElement(WebDriverBy::xpath("//span[text()[contains(.,'Run Report')]]"))->click();

        try {
            $this->driver->wait()->until(
                WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::cssSelector('#app > div > div.report-container > main > div > section > div > table'))
            );

            $this->driver->findElement(WebDriverBy::xpath("//span[text()[contains(.,'Download')]]"))->click();
            /** @var RemoteWebElement $downloadBtn */
            $downloadElement = $this->driver->findElement(WebDriverBy::xpath("//abbr[text()[contains(.,'CSV')]]"));

            $directoryStoreDownloadFile = $this->getDirectoryStoreDownloadFile($startDate, $endDate, $this->getConfig());
            $this->downloadThenWaitUntilComplete($downloadElement, $directoryStoreDownloadFile);
        } catch (TimeOutException $te) {
            $this->logger->notice('No data available for selected date range.');
        } catch (Exception $exception) {
            $this->logger->error($exception->getMessage());
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

    protected function selectRange()
    {
        $this->driver->findElement(WebDriverBy::cssSelector(self::CLASS_RANGE))->click();
        $this->driver->findElement(WebDriverBy::xpath("//div[text()[contains(.,'Custom')]]"))->click();
    }

    protected function selectInterval()
    {
        $this->driver->findElement(WebDriverBy::cssSelector(self::CLASS_INTERVAL))->click();
        $this->driver->findElement(WebDriverBy::xpath("//div[text()[contains(.,'Daily')]]"))->click();
    }

    protected function selectTimezone()
    {
        $this->driver->findElement(WebDriverBy::cssSelector(self::CLASS_TIMEZONE))->click();
        $this->driver->findElement(WebDriverBy::xpath("//div[text()[contains(.,'Pacific/Auckland')]]"))->click();
    }

    protected function selectPlacement()
    {
        $this->driver->findElement(WebDriverBy::cssSelector(self::CLASS_PLACEMENT))->click();
    }
}