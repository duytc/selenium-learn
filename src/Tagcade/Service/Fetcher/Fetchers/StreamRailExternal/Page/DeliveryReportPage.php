<?php

namespace Tagcade\Service\Fetcher\Fetchers\StreamRailExternal\Page;

use Exception;
use Facebook\WebDriver\Exception\NoSuchElementException;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Tagcade\Service\Fetcher\Fetchers\StreamRailExternal\Widget\DateSelectWidget;
use Tagcade\Service\Fetcher\Pages\AbstractPage;
use Tagcade\Service\Fetcher\Params\PartnerParamInterface;
use Tagcade\Service\Fetcher\Params\StreamRailExternal\StreamRailExternalPartnerParamInterface;

class DeliveryReportPage extends AbstractPage
{
    const URL = 'http://partners.streamrail.com/#/report';

    public function getAllTagReports(PartnerParamInterface $param)
    {
        if (!$param instanceof StreamRailExternalPartnerParamInterface) {
            $this->logger->notice('param expect StreamRailParamInterface');
        }

        $this->sleep(2);

        // step 0. select filter
        $this->logger->debug('select filter');
        $this->sleep(2);
        $this->driver->wait()->until(WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::id('modal-overlays')));

        $this->clickDateRange();
        $this->selectDateRange($param->getStartDate(), $param->getEndDate());

        $this->sleep(2);

        // No need to click apply filter when download today report
        $now = new \DateTime();
        if ($now->format('Ymd') != $param->getStartDate()->format('Ymd') && $now->format('Ymd') != $param->getEndDate()->format('Ymd')) {
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