<?php


namespace Tagcade\Service\Fetcher\Fetchers\Lkqd\Page;


use Exception;
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

    public function getAllTagReports(LkqdPartnerParams $params)
    {
        $startDate = $params->getStartDate();
        $endDate = $params->getEndDate();
        $this->selectReportSource();
        $this->selectReportType();
        $this->selectDateRange($startDate, $endDate);

        $this->selectTimezone($params->getTimeZone());
        $this->selectDimensions($params->getDimensions());

        $runReportElement = $this->driver->findElement(WebDriverBy::xpath('//div[@class="row-4"]/button[contains(@class, "run-report-button")]'));
        $this->driver->wait()->until(WebDriverExpectedCondition::elementToBeClickable(WebDriverBy::xpath('//div[@class="row-4"]/button[contains(@class, "run-report-button")]')));
        $runReportElement->click();

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

    protected function logOutSystem()
    {
        $this->driver->findElement(WebDriverBy::xpath('//ul[contains(@class, "navbar-right")]/li[contains(@class, "navigation-bar-item")]/div/a[contains(@class, "caret-button")]/span'))->click();

        $logOutButton = $this->filterElementByTagNameAndText('li', 'Logout');
        if ($logOutButton) {
            $logOutButton->click();
        }
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
            if ($dimensionIndex > 1) {
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
            $this->logger->error(sprintf('cannot find dimension: %s', $text));
        }
    }
}