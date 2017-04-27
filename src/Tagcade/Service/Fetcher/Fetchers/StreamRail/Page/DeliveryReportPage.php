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

        $pageSource = $this->driver->getPageSource();
        $posFirstDimension = strpos($pageSource, 'div aria-haspopup="" aria-controls="ember-basic-dropdown-content-ember');
        $posSecondDimension = strpos($pageSource, 'div aria-haspopup="" aria-controls="ember-basic-dropdown-content-ember', $posFirstDimension + 10);
        $posDateRange = strpos($pageSource, 'div aria-haspopup="" aria-controls="ember-basic-dropdown-content-ember', $posSecondDimension + 10);

        $idFirstDimensionBox = substr($pageSource, $posFirstDimension + 70, 4);
        $idSecondDimensionBox = substr($pageSource, $posSecondDimension + 70, 4);
        $idDateRangeBox = substr($pageSource, $posDateRange + 70, 4);

        $this->selectFirstDimension($param->getPrimaryDimension(), $idFirstDimensionBox);
        $this->selectSecondDimension($param->getSecondaryDimension(), $idSecondDimensionBox);

        if (!(int)$idDateRangeBox) {
            $idDateRangeBox = 1713;
        }

        // select date range
        try {
            $this->driver->findElement(WebDriverBy::id(sprintf('ember-basic-dropdown-trigger-ember%s', $idDateRangeBox)))->click();
            $this->driver->findElement(WebDriverBy::cssSelector(sprintf('#ember-power-select-options-ember%s > li:nth-child(5)', $idDateRangeBox)))->click();
        } catch (\Exception $e) {
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
        $this->driver->findElement(WebDriverBy::id('ember616'))->click();
    }

    /**
     * @param $firstDimension
     * @param $id
     */
    private function selectFirstDimension($firstDimension, $id)
    {
        if (!(int) $id) {
            $id = 1662;
        }
        try {
            $triggerIds = sprintf("ember-basic-dropdown-trigger-ember%s", $id);

            $selectIds = sprintf("ember-power-select-options-ember%s", $id);

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
        } catch (\Exception $e) {

        }
    }

    /**
     * @param $secondDimension
     * @param $id
     */
    private function selectSecondDimension($secondDimension, $id)
    {
        if (!(int)($id)) {
            $id = 1701;
        }

        try {
            $triggerIds = sprintf("ember-basic-dropdown-trigger-ember%s", $id);

            $selectIds = sprintf("ember-power-select-options-ember%s", $id);

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