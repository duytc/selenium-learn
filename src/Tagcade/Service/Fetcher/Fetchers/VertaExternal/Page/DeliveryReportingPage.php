<?php

namespace Tagcade\Service\Fetcher\Fetchers\VertaExternal\Page;

use Exception;
use Facebook\WebDriver\Remote\RemoteWebElement;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Tagcade\Service\Fetcher\Pages\AbstractPage;
use Tagcade\Service\Fetcher\Params\PartnerParamInterface;
use Tagcade\Service\Fetcher\Params\Verta\VertaPartnerParamInterface;

class DeliveryReportingPage extends AbstractPage
{

    public function getAllTagReports(PartnerParamInterface $params)
    {
        if (!($params instanceof VertaPartnerParamInterface)) {
            return;
        }

        $reportLink =  $this->driver->findElement(WebDriverBy::cssSelector('a[href="#/report"]'));
        if(!$reportLink instanceof RemoteWebElement) {
            throw new Exception('Can not find report href. Recheck code base');
        }
        $reportLink->click();
        $this->sleep(10);

        $this->selectReport($params->getReport());
        $this->sleep(3);

        $this->selectCrossReports($params->getCrossReports());
        $this->sleep(10);

        $this->selectDateRanges($params->getStartDate(), $params->getEndDate());
        $this->sleep(5);

        $this->runReportAndDownload();

        $this->logger->debug('Time to wait for the browser closes automatically new tab.');
        $this->sleep(20);
    }

    /**
     * @param \DateTime $startDate
     * @param \DateTime $endDate
     * @return string
     * @throws \Exception
     */
    public function selectDateRanges(\DateTime $startDate, \DateTime $endDate)
    {
        $this->logger->debug(sprintf("Date range: from %s to %s", $startDate->format("Y-m-d"), $endDate->format("Y-m-d")));

        //Open popup select date
        $dateRangeElement = $this->driver->findElement(WebDriverBy::xpath("//*[@ng-model='filters.period']"));
        $dateRangeElement->click();

        //Click Custom date range
        $customDateRangeElement = $this->filterElementByTagNameAndText('li', 'Custom Range');
        if ($customDateRangeElement) {
            $customDateRangeElement->click();
        } else {
            throw new Exception('Can not click Custom Range. Recheck code base');
        }

        //Fill keys to start date
        $this->logger->debug('Entering Start Date');
        $this->sendKeyToStartAndEndDate($startDate->format("m/d/Y"), 'daterangepicker_start');

        //Fill keys to end date
        $this->logger->debug('Entering End Date');
        $this->sendKeyToStartAndEndDate($endDate->format("m/d/Y"), 'daterangepicker_end');

        //Click Apply to close popup
        $this->logger->debug('Click apply');
        $buttonApplyElement = $this->filterElementByTagNameAndText('button', 'Apply');
        if ($buttonApplyElement) {
            $buttonApplyElement->click();

            return;
        }

        throw new Exception('Can not click Apply. Recheck code base');
    }

    /**
     * @param $report
     * @return string
     * @throws \Exception
     */
    public function selectReport($report)
    {
        $this->logger->debug(sprintf("Report: %s", $report));

        $reportElement = $this->driver->findElement(WebDriverBy::xpath("//radio-button-group[@ng-model='filters.report']"));
        $allButtonElements = $reportElement->findElements(WebDriverBy::tagName('label'));
        if (count($allButtonElements) < 1) {
            throw new Exception('Can not find Report button. Need recheck code base');
        }

        $this->logger->debug(sprintf('Report Type: %s with number elements %d', $report, count($allButtonElements)));

        // need to click and exit class if the desired report has a class = active
        foreach ($allButtonElements as $element) {
            if (!$element instanceof RemoteWebElement) {
                continue;
            }
            try {
                if (!$element->isDisplayed()) {
                    continue;
                }

                if ($element->gettext() == $report) {
                    $element->click();
                    return;
                }
            } catch (Exception $e) {
            }
        }

        throw new Exception('Can not find Report button. Need recheck code base');
    }

    /**
     * @param $crossReports
     * @return string
     * @throws \Exception
     */
    public function selectCrossReports($crossReports)
    {
        $this->logger->debug(sprintf("Cross Reports : %s", implode(', ', $crossReports)));

        $crossReportElement = $this->driver->findElement(WebDriverBy::xpath("//checkbox-button-group[@ng-model='filters.cross_report']"));
        $allButtonElements = $crossReportElement->findElements(WebDriverBy::tagName('label'));
        if (count($allButtonElements) < 1) {
            throw new Exception('Can not find Cross Report button. Need recheck code base');
        }

        foreach ($crossReports as $crossReport) {
            foreach ($allButtonElements as $element) {
                if (!$element instanceof RemoteWebElement) {
                    continue;
                }
                try {
                    if (!$element->isDisplayed()) {
                        continue;
                    }

                    if ($element->gettext() == $crossReport) {
                        $element->click();
                        return;
                    }
                } catch (Exception $e) {
                }
            }
        }
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
                    $element->click();
                    return;
                }
            } catch (Exception $e) {
            }
        }

        throw new Exception('Can not find Export to CSV button. Need recheck code base');
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