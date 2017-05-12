<?php

namespace Tagcade\Service\Fetcher\Fetchers\StreamRail\Page;

use Exception;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Tagcade\Service\Fetcher\Fetchers\StreamRail\Widget\DateSelectWidget;
use Tagcade\Service\Fetcher\Pages\AbstractPage;
use Tagcade\Service\Fetcher\Params\PartnerParamInterface;
use Tagcade\Service\Fetcher\Params\StreamRail\StreamRailPartnerParamInterface;

class DeliveryReportPage extends AbstractPage
{
    public function getAllTagReports(PartnerParamInterface $param)
    {
        if (!$param instanceof StreamRailPartnerParamInterface) {
            $this->logger->error('param expect StreamRailParamInterface');
        }

        $this->sleep(2);

        try {
            $this->driver->findElement(WebDriverBy::cssSelector('#confirmation-modal > div.modal-footer > a'))->click();
        } catch (\Exception $e) {

        }
        // step 0. select filter
        $this->logger->debug('select filter');
        $this->sleep(2);
        $this->driver->wait()->until(WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::id('modal-overlays')));

        $this->selectFirstDimension($param->getPrimaryDimension());
        $this->sleep(3);
        $this->selectSecondDimension($param->getSecondaryDimension());

        $this->sleep(3);

        $this->clickDateRange();

        $this->selectDateRange($param->getStartDate(), $param->getEndDate());

        $this->sleep(2);

        $this->driver->findElement(WebDriverBy::xpath('//*[@data-tooltip="Apply Filters"]'))->click();

        $this->driver->wait()->until(WebDriverExpectedCondition::invisibilityOfElementLocated(WebDriverBy::xpath('//*[@data-tooltip="Undo"]')));

        $this->sleep(2);

        $downloadElement = $this->driver->findElement(WebDriverBy::xpath('//*[@data-tooltip="Export to CSV"]'));
        $directoryStoreDownloadFile = $this->getDirectoryStoreDownloadFile($param->getStartDate(), $param->getEndDate(), $this->getConfig());

        $this->downloadThenWaitUntilComplete($downloadElement, $directoryStoreDownloadFile);

        $this->logger->debug('Logout system');
        $this->logOutSystem();
    }

    /**
     * @param \DateTime $startDate
     * @param \DateTime $endDate
     * @return $this
     * @throws Exception
     */
    protected function selectDateRange(\DateTime $startDate, \DateTime $endDate)
    {
        $now = new \DateTime();
        if ($now->format('Ymd') === $startDate->format('Ymd') || $now->format('Ymd') === $endDate->format('Ymd')) {
            throw new Exception('not supported startDate or endDate equal today');
        }

        $dateWidget = new DateSelectWidget($this->driver);
        $dateWidget->setDateRange($startDate, $endDate);
        return $this;
    }

    protected function logOutSystem()
    {
        $pageSource = $this->driver->getPageSource();
        $posAvatar = strpos($pageSource, 'sr-account-image full-height ember-view');
        $idAvatar = substr($pageSource, $posAvatar - 13, 4);

        if (!(int)$idAvatar) {
            $idAvatar = 1581;
        }

        try {
            $this->driver->findElement(WebDriverBy::cssSelector(sprintf('#ember%s', $idAvatar)))->click();
        } catch (\Exception $e) {
            $this->driver->findElement(WebDriverBy::cssSelector('#ember1163'))->click();
        }

        $this->sleep(3);

        $logOutElement = $this->filterElementByTagNameAndText('li', 'Log out');
        if ($logOutElement) {
            $logOutElement->click();
        }
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

        $adSourceElement = $this->filterElementByTagNameAndText('span', 'Ad Source');
        if ($adSourceElement) {
            $adSourceElement->click();
        }

        $dimensionElement = $this->filterElementByTagNameAndText('li', $firstDimension);
        if ($dimensionElement) {
            $dimensionElement->click();
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

        $adSourceElement = $this->filterElementByTagNameAndText('span', 'Select Dimension');
        if ($adSourceElement) {
            $adSourceElement->click();
        }

        $dimensionElement = $this->filterElementByTagNameAndText('li', $secondDimension);
        if ($dimensionElement) {
            $dimensionElement->click();
        }
    }

    /**
     *
     */
    private function clickDateRange()
    {
        $toDayElement = $this->filterElementByTagNameAndText('span', 'Today');
        if ($toDayElement) {
            $toDayElement->click();
        }

        $customElement = $this->filterElementByTagNameAndText('li', 'Custom');
        if ($customElement) {
            $customElement->click();
        }
    }
}