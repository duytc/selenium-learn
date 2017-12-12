<?php


namespace Tagcade\Service\Fetcher\Fetchers\LkqdDemandDeals\Page;

use Exception;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverElement;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Tagcade\Service\Fetcher\Fetchers\Lkqd\Widget\DateSelectWidget;
use Tagcade\Service\Fetcher\Fetchers\Lkqd\Widget\ReportSourceSelectWidget;
use Tagcade\Service\Fetcher\Fetchers\Lkqd\Widget\ReportTypeSelectWidget;
use Tagcade\Service\Fetcher\Pages\AbstractPage;
use Tagcade\Service\Fetcher\Params\Lkqd\LkqdPartnerParams;

class ReportPage extends AbstractPage
{
    const URL = 'https://ui.lkqd.com/reports';

    const DAILY_REPORT_INDEX = 3;
    const SUPPLY_SOURCE_INDEX = 2;
    const DOWNLOAD_BUTTON_INDEX = 5;
    const METRIC_KEYS = '% Views';

    public function getAllTagReports(LkqdPartnerParams $params)
    {
        $startDate = $params->getStartDate();
        $endDate = $params->getEndDate();
        $this->selectReportSource();
        $this->selectReportType();
        $this->selectDateRange($startDate, $endDate);

        $this->selectTimezone($params->getTimeZone());
        $this->selectDimensions($params->getDimensions());

        //check metric must be selected
        if (empty($params->getMetrics())) {
            throw new \Exception('At least one metric must be selected. Please check metric parameters.');
        }

        $this->selectMetrics($this->driver, $params->getMetrics());

        $runReportElement = $this->driver->findElement(WebDriverBy::xpath('//div[@class="row-4"]/button[contains(@class, "run-report-button")]'));

        $runReportElement->click();

        /** RemoveWebDriver $downloadElement */
        $downloadElement = $this->driver->findElement(WebDriverBy::xpath('//div[@class="row-4"]/button[contains(@class, "download-button")]'));
        $this->driver->wait()->until(WebDriverExpectedCondition::elementToBeClickable(WebDriverBy::xpath('//div[@class="row-4"]/button[contains(@class, "download-button")]')));
        $directoryStoreDownloadFile = $this->getDirectoryStoreDownloadFile($startDate, $endDate, $this->getConfig());
        $this->downloadThenWaitUntilComplete($downloadElement, $directoryStoreDownloadFile);
    }

    protected function selectDateRange(\DateTime $startDate, \DateTime $endDate)
    {
        $dateWidget = new DateSelectWidget($this->driver, $this->logger);
        $dateWidget->setDateRange($startDate, $endDate);
        return $this;
    }

    protected function selectReportType()
    {
        $reportTypeWidget = new ReportTypeSelectWidget($this->driver, $this->logger);
        $reportTypeWidget->setReportType(self::DAILY_REPORT_INDEX);
        return $this;
    }

    protected function selectReportSource()
    {
        $reportSourceWidget = new ReportSourceSelectWidget($this->driver, $this->logger);
        $reportSourceWidget->setReportSource(self::SUPPLY_SOURCE_INDEX);
        return $this;
    }

    /**
     * @param $timezone
     */
    private function selectTimezone($timezone)
    {
        $this->driver->findElement(WebDriverBy::cssSelector('#reports > div > div.report-controls.ng-scope > div.row-1 > div.timezone-box > div > button'))->click();

        $ulDropDownMenu = $this->driver->findElement(WebDriverBy::cssSelector('#reports > div > div.report-controls.ng-scope > div.row-1 > div.timezone-box > div > ul'));
        $lis = $ulDropDownMenu->findElements(WebDriverBy::tagName('a'));
        foreach ($lis as $li) {
            if ($li->getText() === $timezone) {
                $li->click();
                break;
            }
        }
    }

    /**
     * @param array $dimensions
     */
    private function selectDimensions(array $dimensions)
    {
        $firstDimensionDiv = $this->removeAllDefaultDimensions();

        $dimensionIndex = 0;
        $dimensionSelector = $firstDimensionDiv;
        foreach ($dimensions as $dimension) {
            $dimensionIndex++;
            if ($dimensionIndex >= 1) {
                try {
                    $dimensionSelector = $this->driver->findElement(WebDriverBy::cssSelector(sprintf('#reports > div > div.report-controls.ng-scope > div.row-2 > div:nth-child(%d) > div', $dimensionIndex)));
                } catch (\Exception $e) {

                }
            }

            $this->choseDimension($dimensionSelector, $dimension);
        }
    }

    /**
     * @return WebDriverElement
     * remove all default dimensions
     */
    private function removeAllDefaultDimensions()
    {
        $firstDimensionDiv = $this->driver->findElement(WebDriverBy::cssSelector('#reports > div > div.report-controls.ng-scope > div.row-2 > div.dimension-box.ng-scope.labeled.selected > div'));
        $this->choseDimension($firstDimensionDiv, '+/- Dimension');

        return $firstDimensionDiv;
    }

    /**
     * @param WebDriverElement $driverElement
     * @param $text
     * @throws Exception
     */
    private function choseDimension(WebDriverElement $driverElement, $text)
    {
        $driverElement->click();

        $aElements = $driverElement->findElements(WebDriverBy::tagName('a'));

        $isFound = false;
        foreach ($aElements as $aElement) {
            if (strtolower(trim($aElement->getText())) === strtolower(trim($text))) {
                $aElement->click();
                $isFound = true;
                break;
            }
        }

        //if dimension not found throw exception
        if (!$isFound) {
            $this->logger->notice(sprintf('cannot find dimension: %s', $text));
        }
    }

    /**
     * @param RemoteWebDriver $driver
     * @param array $metrics
     */
    private function selectMetrics(RemoteWebDriver $driver, array $metrics)
    {
        $metricsButton = $driver->findElement(WebDriverBy::cssSelector('#reports > div > div.report-controls > div.row-2 > div.report-metrics-box > button'));
        $this->logger->debug('Show metrics');
        $metricsButton->click();

        try {
            $unselectedAllMetrics = $driver->findElement(WebDriverBy::cssSelector('#reports > div > div.report-controls > div.row-2 > div.animate-show-hide > div.box > div.selected-box > h3 > span > a'));
            $this->logger->debug('click remove default metrics');
            $this->sleep(1);
            $unselectedAllMetrics->click();

            $metricIndex = 0;
            foreach ($metrics as $metric) {
                if (is_numeric($metric)) {
                    $metric = trim($metric) . self::METRIC_KEYS;
                }
                $metricIndex++;
                if ($metricIndex >= 1) {
                    try {
                        $metricSelector = $this->driver->findElement(WebDriverBy::cssSelector('#reports > div > div.report-controls > div.row-2 > div.report-selector > div.box > div.unselected-box > ul'));
                        $this->choseMetric($metricSelector, $metric);
                    } catch (\Exception $e) {

                    }
                }
            }

        } catch (\Exception $ex) {
            $this->logger->debug('Use the metrics result have chosen in the previous.');
        }

    }

    /**
     * @param WebDriverElement $driverElement
     * @param $text
     * @throws Exception
     */
    private function choseMetric(WebDriverElement $driverElement, $text)
    {
        $aElements = $driverElement->findElements(WebDriverBy::tagName('li'));

        $isFound = false;
        foreach ($aElements as $aElement) {
            if (strtolower(trim($aElement->getText())) === strtolower(trim($text))) {
                $aElement->click();
                $isFound = true;
                break;
            }
        }

        //if metric not found throw exception
        if (!$isFound) {
            $this->logger->notice(sprintf('cannot find metric: %s', $text));
        }
    }
}