<?php

namespace Tagcade\Service\Fetcher\Fetchers\VertaInternal\Page;

use DateTime;
use Exception;
use Facebook\WebDriver\Remote\RemoteWebElement;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Tagcade\Exception\RuntimeException;
use Tagcade\Service\Fetcher\Pages\AbstractPage;
use Tagcade\Service\Fetcher\Params\PartnerParamInterface;
use Tagcade\Service\Fetcher\Params\Verta\VertaPartnerParamInterface;

class DeliveryReportingPage extends AbstractPage
{
    const URL = 'https://ssp.vertamedia.com/#/reports/details/';

    public function getAllTagReports(PartnerParamInterface $params)
    {
        if (!($params instanceof VertaPartnerParamInterface)) {
            return;
        }

        $this->logger->debug('redirect to report page');
        $this->driver->navigate()->to(self::URL);
        //need to wait
        $this->driver->wait(30)->until(WebDriverExpectedCondition::invisibilityOfElementLocated(WebDriverBy::id('#date-picker-tcmxpgb')));

        $this->selectReport($params->getReport(), $params->getDataSourceId());
        $this->selectCrossReport($params->getCrossReport(), $params->getDataSourceId());
        $this->driver->wait(30)->until(WebDriverExpectedCondition::invisibilityOfElementLocated(WebDriverBy::cssSelector('div.ui-grid-header > div > div > div > div > div > div.ui-grid-coluiGrid-000A > div.sortable > div > span')));

        $this->selectDateRanges($params->getStartDate(), $params->getEndDate());
        $this->driver->wait(30)->until(WebDriverExpectedCondition::invisibilityOfElementLocated(WebDriverBy::cssSelector('div.ui-grid-header > div > div > div > div > div > div.ui-grid-coluiGrid-000A > div.sortable > div > span')));

        $this->runReportAndDownload();

        $this->logger->debug('Time to wait for the browser closes automatically new tab.');
        $this->sleep(60);
    }

    /**
     * @param $report
     * @param null $dataSourceId
     * @return string
     * @throws \Exception
     */
    public function selectReport($report, $dataSourceId = null)
    {
        $this->logger->debug(sprintf("Report type: %s", $report));
        if ($report == null) {
            throw new \Exception(sprintf("Report is null value. Need recheck on Data Source id = %s", $report, $dataSourceId));
        }

        $reportElement = $this->driver->findElement(WebDriverBy::cssSelector("application > div > div > reports > div > div.content > div > report-container > div > section > reports-grid-filter > div > div:nth-child(1) > div.slice > div > div > div"));
        $optionElements = $reportElement->findElements(WebDriverBy::tagName('button'));

        if (count($optionElements) < 1) {
            return null;
        }

        $click = false;
        foreach ($optionElements as $optionElement) {
            if (!$optionElement instanceof RemoteWebElement) {
                continue;
            }

            if ($report == $optionElement->getText()) {
                $optionElement->click();
                $click = true;
                break;
            }
        }

        if ($click == false) {
            throw new \Exception(sprintf("Report %s not correct. Need recheck on Data Source id = %s", $report, $dataSourceId));
        }
    }

    /**
     * @param $crossReport
     * @param null $dataSourceId
     * @return string
     * @throws \Exception
     */
    public function selectCrossReport($crossReport, $dataSourceId = null)
    {
        $this->logger->debug("Cross Report:");

        if ($crossReport == null) {
            throw new \Exception(sprintf("Cross Report is null value. Need recheck on Data Source id = %s", $crossReport, $dataSourceId));
        }

        $crossReportElement = $this->driver->findElement(WebDriverBy::cssSelector("application > div > div > reports > div > div.content > div > report-container > div > section > reports-grid-filter > div > div.slice-filter > div.slice > div > div > additional-slice-picker"));
        $optionElements = $crossReportElement->findElements(WebDriverBy::tagName('button'));
        $this->sleep(2);
        $click = false;

        if (count($optionElements) < 1) {
            return null;
        }

        foreach ($optionElements as $element) {
            if (!$element instanceof RemoteWebElement) {
                continue;
            }

            try {
                if (!$element->isDisplayed()) {
                    continue;
                }
                if ($crossReport == $element->getText()) {
                    if (strpos($element->getAttribute('class'), 'btn-primary')){
                        $click = true;
                        break;
                    } else {
                        $this->logger->debug(sprintf("Cross Report: %s ", $crossReport));
                        $element->click();
                        $click = true;
                        break;
                    }
                }
            } catch (Exception $e) {

            }
        }

        if ($click == false) {
            throw new \Exception(sprintf("Cross Report %s not correct. Need recheck on Data Source id = %s", $crossReport, $dataSourceId));
        }
    }

    /**
     * @param DateTime $startDate
     * @param DateTime $endDate
     * @return string
     * @throws \Exception
     */
    public function selectDateRanges(DateTime $startDate, DateTime $endDate)
    {
        $this->logger->debug(sprintf("Date range: from %s to %s", $startDate->format("Y-m-d"), $endDate->format("Y-m-d")));
        $dateRangeElement = $this->driver->findElement(WebDriverBy::cssSelector("application > div > div > reports > div > div.content > div > report-container > div > section.margin-top-30 > div.inline-block"));
        $dateRangeElement->click();

        $customDateRangeElement = $this->filterElementByTagNameAndText('li', 'Custom Range');

        if ($customDateRangeElement) {
            $customDateRangeElement->click();
        } else {
            throw new RuntimeException('Can not click Custom Range. Recheck code base');
        }

        $this->logger->debug('Entering Start Date');
        $this->sendKeyToStartAndEndDate($startDate->format("m/d/Y"), 'daterangepicker_start');

        $this->logger->debug('Entering End Date');
        $this->sendKeyToStartAndEndDate($endDate->format("m/d/Y"), 'daterangepicker_end');

        $this->logger->debug('Click apply');
        $buttonApplyElement = $this->filterElementByTagNameAndText('button', 'Apply');

        if ($buttonApplyElement) {
            $buttonApplyElement->click();

            return;
        }

        throw new RuntimeException('Can not click Apply. Recheck code base');
    }

    /**
     * @throws \Exception
     */
    public function runReportAndDownload()
    {
        $this->logger->debug("Find download button");

        $allButtonElements = $this->driver->findElements(WebDriverBy::tagName('button'));
        if (count($allButtonElements) < 1) {
            throw new Exception('Can not find Export to CSV button. Need recheck code base');
        }

        foreach ($allButtonElements as $element) {
            if (!$element instanceof RemoteWebElement) {
                continue;
            }
            try {
                if (!$element->isDisplayed()) {
                    continue;
                }
                if (strtolower($element->getAttribute('aria-label')) == strtolower('Export to CSV')) {
                    $this->logger->debug("Click download button");
                    $element->click();

                    return;
                }
            } catch (Exception $e) {
            }
        }

        throw new Exception('Can not find Export to CSV button. Need recheck code base');
    }

    public function clickExportToCSV()
    {
        $this->logger->debug("Try click button Export to CSV");
        $exportToCSVButton = $this->filterElementByTagNameAndText('span', "Export to .CSV");
        if ($exportToCSVButton) {
            $exportToCSVButton->click();
            $this->logger->debug('Click download report');

            return;
        }
    }

    /**
     * @param $date
     * @param $attribute
     * @return null|void
     */
    private function sendKeyToStartAndEndDate($date, $attribute)
    {
        $classElements = $this->driver->findElements(WebDriverBy::tagName('input'));
        if (count($classElements) < 1) {
            return null;
        }

        foreach ($classElements as $element) {
            if (!$element instanceof RemoteWebElement) {
                continue;
            }

            try {
                if (!$element->isDisplayed()) {
                    continue;
                }

                if (strtolower($element->getAttribute('name')) == strtolower($attribute)) {
                    $element->clear();
                    $element->sendKeys($date);

                    return;
                }
            } catch (Exception $e) {

            }
        }

        return null;
    }
}