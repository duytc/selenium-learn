<?php

namespace Tagcade\Service\Fetcher\Fetchers\StreamRail\Page;

use Exception;
use Facebook\WebDriver\Exception\NoSuchElementException;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Tagcade\Service\Fetcher\Fetchers\StreamRail\Widget\DateSelectWidget;
use Tagcade\Service\Fetcher\Pages\AbstractPage;
use Tagcade\Service\Fetcher\Params\PartnerParamInterface;
use Tagcade\Service\Fetcher\Params\StreamRail\StreamRailPartnerParamInterface;

class DeliveryReportPage extends AbstractPage
{
    const URL = 'http://partners.streamrail.com/#/report';

    public function getAllTagReports(PartnerParamInterface $param)
    {
        if (!$param instanceof StreamRailPartnerParamInterface) {
            $this->logger->notice('param expect StreamRailParamInterface');
        }

        $this->sleep(2);

        // step 0. select filter
        $this->logger->debug('select filter');
        $this->sleep(2);
        $this->driver->wait()->until(WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::id('modal-overlays')));

        if ($param->getPrimaryDimension() !== 'Ad Source')
            $this->selectFirstDimension($param->getPrimaryDimension());

        $this->selectSecondDimension($param->getSecondaryDimension());
        $this->clickDateRange();
        $this->selectDateRange($param->getStartDate(), $param->getEndDate());

        $this->sleep(2);

        // No need to click apply filter when download today report
        $now = new \DateTime();
        if ($now->format('Ymd') === $param->getStartDate()->format('Ymd') && $now->format('Ymd') === $param->getEndDate()->format('Ymd')) {

            // case 1: primaryDimemsion = null, secondDimension = null -> no need to click
            // case 2: primaryDimension == Ad Source, secondDimension == null -> no need to check
            if ($param->getPrimaryDimension() === null && $param->getSecondaryDimension() === null) {
                // no need to click
            } elseif ($param->getPrimaryDimension() === 'Ad Source' && $param->getSecondaryDimension() === null) {
                // no need to click
            } else {
                $this->driver->findElement(WebDriverBy::xpath('//*[@data-tooltip="Apply Filters"]'))->click();
            }
        } else {
            $this->driver->findElement(WebDriverBy::xpath('//*[@data-tooltip="Apply Filters"]'))->click();
        }

        $this->driver->wait()->until(WebDriverExpectedCondition::invisibilityOfElementLocated(WebDriverBy::xpath('//*[@data-tooltip="Undo"]')));

        $this->sleep(2);

        $downloadElement = $this->driver->findElement(WebDriverBy::xpath('//*[@data-tooltip="Export to CSV"]'));
        $directoryStoreDownloadFile = $this->getDirectoryStoreDownloadFile($param->getStartDate(), $param->getEndDate(), $this->getConfig());

        $this->downloadThenWaitUntilComplete($downloadElement, $directoryStoreDownloadFile);
    }

    /**
     * @param \DateTime $startDate
     * @param \DateTime $endDate
     * @return $this
     * @throws Exception
     */
    protected function selectDateRange(\DateTime $startDate, \DateTime $endDate)
    {
        /*
         * Now we support download today report, so that do not need to set max day as yesterday
         * TODO: remove when stable
         */
//        $now = new \DateTime();
//        if ($now->format('Ymd') === $startDate->format('Ymd') || $now->format('Ymd') === $endDate->format('Ymd')) {
//            throw new Exception('not supported startDate or endDate equal today');
//        }

        $dateWidget = new DateSelectWidget($this->driver);
        $dateWidget->setDateRange($startDate, $endDate);
        return $this;
    }

    /**
     * @param $firstDimension
     */
    private function selectFirstDimension($firstDimension)
    {
        if (empty($firstDimension)) {
            return;
        }

        $pageSource = $this->driver->getPageSource();
        if (!strpos(strtolower($pageSource), strtolower('Primary Dimension'))) {
            return;
        }

        $firstDimensionElement = $this->driver->findElement(WebDriverBy::xpath('//div[contains(@class, "sr-report--filters") and contains(@class ,"no-horizontal-margin")]/div[1]/div[2]/div[1]'));
        if ($firstDimensionElement) {
            $firstDimensionElement->click();
            $dropDownElement = $this->driver->findElement(WebDriverBy::xpath(sprintf('//ul[contains(@class, "ember-power-select-options") and contains(@class, "ember-view")]')));
            $listElements = $dropDownElement->findElements(WebDriverBy::tagName('li'));
            foreach ($listElements as $listElement) {
                $l = $listElement->getText();
                if (trim($firstDimension) == $l) {
                    $listElement->click();
                    break;
                }
            }
        }
    }

    /**
     * @param $secondDimension
     */
    private function selectSecondDimension($secondDimension)
    {
        if (empty($secondDimension)) {
            return;
        }

        $pageSource = $this->driver->getPageSource();
        if (!strpos(strtolower($pageSource), strtolower('Secondary Dimension'))) {
            return;
        }

        $secondDimensionElement = $this->driver->findElement(WebDriverBy::xpath('//div[contains(@class, "sr-report--filters") and contains(@class ,"no-horizontal-margin")]/div[2]/div[2]/div[1]/div[1]'));
        if ($secondDimensionElement) {
            $secondDimensionElement->click();
            $dropDownElement = $this->driver->findElement(WebDriverBy::xpath(sprintf('//ul[contains(@class, "ember-power-select-options") and contains(@class, "ember-view")]')));
            $listElements = $dropDownElement->findElements(WebDriverBy::tagName('li'));
            foreach ($listElements as $listElement) {
                $l = $listElement->getText();
                if (trim($secondDimension) == $l) {
                    $listElement->click();
                    break;
                }
            }
        }
    }

    /**
     *
     */
    private function clickDateRange()
    {
        try {
            $dateElement = $this->driver->findElement(WebDriverBy::xpath('//div[contains(@class, "sr-date-range")]/div[1]/div[1]'));
            if ($dateElement) {
                $dateElement->click();
                $this->sleep(1);
                $dropDownElement = $this->driver->findElement(WebDriverBy::xpath('//ul[contains(@class, "ember-power-select-options") and contains(@class, "ember-view")]/li[contains(., "Custom")]'));
                $elements = $this->driver->findElements(WebDriverBy::tagName('li'));
                foreach ($elements as $element) {
                    if ('Custom' == $element->getText()) {
                        $this->sleep(1);
                        $element->click();
                        break;
                    }
                }
            }
        } catch (NoSuchElementException $noSuchElementException) {
            $dateElement = $this->driver->findElement(WebDriverBy::xpath('//div[contains(@class, "sr-report--filters") and contains(@class ,"no-horizontal-margin")]/div[1]/div[2]/div[1]/div[1]'));
            if ($dateElement) {
                $dateElement->click();
                $dropDownElement = $this->driver->findElement(WebDriverBy::xpath('//ul[contains(@class, "ember-power-select-options") and contains(@class, "ember-view")]/li[contains(., "Custom")]'));
                if ($dropDownElement) {
                    $dropDownElement->click();
                }
            }
        }
    }
}