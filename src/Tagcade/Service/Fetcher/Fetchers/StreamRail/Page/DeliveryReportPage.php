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
        
        // step 0. select filter
        $this->logger->debug('select filter');
        $this->sleep(2);
        $this->driver->wait()->until(WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::id('modal-overlays')));

        $this->selectFirstDimension($param->getFirstDimension());
        $this->selectSecondDimension($param->getSecondDimension());

        // select date range
        try {
            $this->driver->findElement(WebDriverBy::id('ember-basic-dropdown-trigger-ember1713'))->click();
            $this->driver->findElement(WebDriverBy::cssSelector('#ember-power-select-options-ember1713 > li:nth-child(5)'))->click();
        } catch (\Exception $e){
            $this->driver->findElement(WebDriverBy::id('ember-basic-dropdown-trigger-ember1247'))->click();
            $this->driver->findElement(WebDriverBy::cssSelector('#ember-power-select-options-ember1247 > li:nth-child(5)'))->click();
        }

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
     * @param bool $isDimension
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
        try {
            $this->driver->findElement(WebDriverBy::cssSelector('#ember1581'))->click();
        } catch(\Exception $e){
            $this->driver->findElement(WebDriverBy::cssSelector('#ember1163'))->click();
        }

        $this->sleep(3);
        $this->driver->findElement(WebDriverBy::id('ember616'))->click();
    }

    /**
     * @param $firstDimension
     */
    private function selectFirstDimension($firstDimension)
    {
        try {
            $triggerIds = "ember-basic-dropdown-trigger-ember1662";

            $selectIds = "ember-power-select-options-ember1662";

            $triggerDimensionsChosen = $this->driver->findElement(WebDriverBy::id($triggerIds));
            $triggerDimensionsChosen->click();

            $selectDimensionsChosen = $this->driver->findElement(WebDriverBy::id($selectIds));
            $liElements = $selectDimensionsChosen->findElements(WebDriverBy::tagName('li'));

            foreach ($liElements as $liElement) {
                if ($liElement->getText() == $firstDimension) {
                    $liElement->click();
                    break;
                }
            }
        } catch(\Exception $e){

        }
    }

    /**
     * @param $secondDimension
     */
    private function selectSecondDimension($secondDimension)
    {
        try {
            $triggerIds = "ember-basic-dropdown-trigger-ember1701";

            $selectIds = "ember-power-select-options-ember1701";

            $triggerDimensionsChosen = $this->driver->findElement(WebDriverBy::id($triggerIds));
            $triggerDimensionsChosen->click();

            $selectDimensionsChosen = $this->driver->findElement(WebDriverBy::id($selectIds));
            $liElements = $selectDimensionsChosen->findElements(WebDriverBy::tagName('li'));

            foreach ($liElements as $liElement) {
                if ($liElement->getText() == $secondDimension) {
                    $liElement->click();
                    break;
                }
            }
        } catch (\Exception $e) {

        }
    }
}