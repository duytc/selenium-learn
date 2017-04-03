<?php

namespace Tagcade\Service\Fetcher\Fetchers\SpringServe\Page;

use Facebook\WebDriver\Exception\TimeOutException;
use Facebook\WebDriver\Remote\RemoteWebElement;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverElement;
use Tagcade\Service\Fetcher\Fetchers\PulsePoint\Page\AbstractPage;
use Tagcade\Service\Fetcher\Fetchers\SpringServe\Widget\DateSelectWidget;

class DeliveryReportingPage extends AbstractPage
{
    const URL = 'https://video.springserve.com/reports';

    public function getAllTagReports(\DateTime $startDate, \DateTime $endDate)
    {
        $this->logger->info('get all tag');

        $this->selectTimeZone();
        $this->selectDateRange($startDate, $endDate);

        /** @var RemoteWebElement $runReportBtn */
        $runReportBtn = $this->driver->findElement(WebDriverBy::name('commit'));
        $runReportBtn->click();

        try {
            /** @var RemoteWebElement $downloadElement */
            $downloadElement = $this->driver->findElement(WebDriverBy::id('export_link'));
            $downloadElement->click();

            $directoryStoreDownloadFile = $this->getDirectoryStoreDownloadFile($startDate, $endDate, $this->getConfig());
            $this->downloadThenWaitUntilComplete($downloadElement, $directoryStoreDownloadFile);

        } catch (TimeOutException $te) {
            $this->logger->error('No data available for selected date range.');
        } catch (\Exception $exception) {
            $this->logger->error($exception->getMessage());
        }
    }

    protected function selectDateRange(\DateTime $startDate, \DateTime $endDate)
    {
        $dateWidget = new DateSelectWidget($this->driver);
        $dateWidget->setDateRange($startDate, $endDate);
        return $this;
    }

    protected function selectTimeZone()
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
            if ($liElement->getText() == 'UTC') {
                $liElement->click();
                break;
            }
        }
    }
}