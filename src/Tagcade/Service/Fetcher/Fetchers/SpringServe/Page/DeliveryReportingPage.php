<?php

namespace Tagcade\Service\Fetcher\Fetchers\SpringServe\Page;

use Exception;
use Facebook\WebDriver\Exception\TimeOutException;
use Facebook\WebDriver\Remote\RemoteWebElement;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverElement;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Tagcade\Service\Fetcher\Fetchers\SpringServe\Widget\DateSelectWidget;
use Tagcade\Service\Fetcher\Pages\AbstractPage;
use Tagcade\Service\Fetcher\Params\PartnerParamInterface;
use Tagcade\Service\Fetcher\Params\SpringServe\SpringServePartnerParamInterface;

class DeliveryReportingPage extends AbstractPage
{
    const URL = 'https://video.springserve.com/reports';
    const TIME_OUT = 120;

    public function getAllTagReports(PartnerParamInterface $param)
    {
        $this->logger->info('get all tag');

        if (!$param instanceof SpringServePartnerParamInterface) {
            $this->logger->notice('params must be instance of SpringServe Partner Param');
            return;
        }

        $this->selectDateRange($param->getStartDate(), $param->getEndDate());
        $this->selectInterval($param->getInterval());
        $this->selectTimeZone($param->getTimeZone());
        $this->selectDimensions($param->getDimensions());

        /** @var RemoteWebElement $runReportBtn */
        $runReportBtn = $this->driver->findElement(WebDriverBy::name('commit'));
        $runReportBtn->click();

        sleep(3);
        $this->driver->wait(self::TIME_OUT)->until(WebDriverExpectedCondition::invisibilityOfElementLocated(WebDriverBy::id('spinner')));
        sleep(3);
        
        try {
            $buttonElements = $this->driver->findElements(WebDriverBy::tagName('button'));
            foreach ($buttonElements as $buttonElement) {
                if ($buttonElement->getText() == 'View Preview') {
                    $buttonElement->click();
                }
            }
        } catch (\Exception $e) {

        }

        sleep(3);

        try {
            /** @var RemoteWebElement $downloadElement */
            $downloadElement = $this->driver->findElement(WebDriverBy::id('export_link'));
            $directoryStoreDownloadFile = $this->getDirectoryStoreDownloadFile($param->getStartDate(), $param->getEndDate(), $this->getConfig());
            $this->downloadThenWaitUntilComplete($downloadElement, $directoryStoreDownloadFile);

        } catch (TimeOutException $te) {
            $this->logger->notice('No data available for selected date range.');
        } catch (Exception $exception) {
            $this->logger->error($exception->getMessage());
        }

        sleep(3);
    }

    protected function selectDateRange(\DateTime $startDate, \DateTime $endDate)
    {
        $dateWidget = new DateSelectWidget($this->driver);
        $dateWidget->setDateRange($startDate, $endDate);
        return $this;
    }

    /**
     * @param $timeZone
     */
    protected function selectTimeZone($timeZone)
    {
        /**
         * @var WebDriverElement $timeZoneChosen
         */
        $timeZoneChosen = $this->driver->findElement(WebDriverBy::id('timezone_chosen'));
        $timeZoneChosen->click();

        /**
         * @var WebDriverElement[] $liElements
         */
        $liElements = $timeZoneChosen->findElements(WebDriverBy::tagName('li'));

        foreach ($liElements as $liElement) {
            if ($this->reFormatTimeZone($liElement->getText()) == $this->reFormatTimeZone($timeZone)) {
                $liElement->click();
                break;
            }
        }
    }

    /**
     * @param $interval
     */
    private function selectInterval($interval)
    {
        /**
         * @var WebDriverElement $intervalChosen
         */
        $intervalChosen = $this->driver->findElement(WebDriverBy::id('interval_chosen'));
        $intervalChosen->click();

        /**
         * @var WebDriverElement[] $liElements
         */
        $liElements = $intervalChosen->findElements(WebDriverBy::tagName('li'));

        foreach ($liElements as $liElement) {
            if ($liElement->getText() == $interval) {
                $liElement->click();
                break;
            }
        }
    }

    /**
     * @param array $dimensions
     */
    private function selectDimensions(array $dimensions)
    {
        $this->deleteOldDimensions();

        foreach ($dimensions as $dimension) {
            $dimensionChosen = $this->driver->findElement(WebDriverBy::id('dimensions_chosen'));
            $dimensionChosen->click();

            /**
             * @var WebDriverElement[] $liElements
             */
            $liElements = $dimensionChosen->findElements(WebDriverBy::tagName('li'));

            foreach ($liElements as $liElement) {
                if ($liElement->getText() == $dimension) {
                    $liElement->click();

                    break;
                }
            }
        }
    }

    /**
     *
     */
    private function deleteOldDimensions()
    {
        /**
         * @var WebDriverElement $dimensionChosen
         */
        $dimensionChosen = $this->driver->findElement(WebDriverBy::cssSelector('#dimensions_chosen > ul'));

        /**
         * @var WebDriverElement[] $liElements
         */
        $liElements = $dimensionChosen->findElements(WebDriverBy::tagName('a'));

        foreach ($liElements as $liElement) {
            $liElement->click();
        }
    }

    /**
     * @param $timeZone
     * @return mixed
     */
    private function reFormatTimeZone($timeZone)
    {
        $timeZone = str_replace('/', "", $timeZone);
        $timeZone = str_replace(' ', "", $timeZone);
        return $timeZone;
    }
}