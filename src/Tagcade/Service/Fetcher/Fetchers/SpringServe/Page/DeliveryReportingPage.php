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
    const URL = 'https://video.springserve.com/reports?date_range=Yesterday&interval=&timezone=Etc%2FUTC&dimensions%5B%5D=supply_tag_id&declared_domain=&detected_domain=';

    public function getAllTagReports(\DateTime $startDate, \DateTime $endDate)
    {
        $this->logger->info('get all tag');

        $this->selectTimeZone();
        $this->selectDateRange($startDate, $endDate);

        /** @var RemoteWebElement $runReportBtn */
        $runReportBtn = $this->driver->findElement(WebDriverBy::name('commit'));
        $runReportBtn->click();

        sleep(7);

        try {
            /** @var RemoteWebElement $downloadElement */
            $downloadElement = $this->driver->findElement(WebDriverBy::id('export_link'));
            $downloadElement->click();

            $directoryStoreDownloadFile = $this->getDirectoryStoreDownloadFile($startDate, $endDate, $this->getConfig());
            $this->downloadThenWaitUntilComplete($downloadElement, $directoryStoreDownloadFile);
            $this->logoutSystem();

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

    protected function logoutSystem()
    {
        $logOutChosen = $this->driver->findElement(WebDriverBy::id('navigation-toggle'));
        $logOutChosen->click();
        /**
         * @var WebDriverElement[] $liElements
         */
        $liElements = $logOutChosen->findElements(WebDriverBy::tagName('li'));

        foreach ($liElements as $liElement) {
            if ($liElement->getText() == 'Sign out') {
                $liElement->click();
                break;
            }
        }
    }
}