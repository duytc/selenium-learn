<?php

namespace Tagcade\Service\Fetcher\Fetchers\Sovrn\Page;

use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Tagcade\Service\Fetcher\Fetchers\Sovrn\Widget\DateSelectWidget;
use Tagcade\Service\Fetcher\Pages\AbstractPage;

class EarningPage extends AbstractPage
{
    const URL = 'https://meridian.sovrn.com/#/view/account/my_downloads';

    public function getAllTagReports(\DateTime $startDate, \DateTime $endDate)
    {
        // popup calendar
        $this->sleep(2);
        $this->navigate();
        $this->sleep(2);
        $this->driver->wait()->until(WebDriverExpectedCondition::presenceOfAllElementsLocatedBy(WebDriverBy::id('section-account-download-adstats')));
        $dateCalendarContainer = $this->driver->findElement(WebDriverBy::id('section-account-download-adstats'));
        $this->driver->wait()->until(WebDriverExpectedCondition::presenceOfAllElementsLocatedBy(WebDriverBy::className('date-range-calendar')));
        $elements = $dateCalendarContainer->findElements(WebDriverBy::className('date-range-calendar'));

        $this->logger->debug('Popup browser');
        foreach ($elements as $e) {
            $att = $e->getAttribute('data-rangetype');
            if ($att == 'start') {
                $this->driver->wait()->until(WebDriverExpectedCondition::visibilityOf($e));

                $e->click();
                $this->sleep(2);
                break;
            }
        }

        $this->driver->wait()->until(
            WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::id('calendar_downloads_account-downloads-adstats'))
        );

        $this->logger->debug(sprintf('Setting start date %s', $startDate->format('Y-m-d')));

        // Step 1. Set date range
        $this->selectDateRange($startDate, $endDate);

        $this->driver->wait()->until(
            WebDriverExpectedCondition::invisibilityOfElementLocated(WebDriverBy::id('calendar_downloads_account-downloads-adstats'))
        );

        $this->sleep(1);
        // breakdown by day
        $this->logger->debug('Setting breakdown by days');
        $this->driver->wait()->until(
            WebDriverExpectedCondition::elementToBeClickable(WebDriverBy::id('adstats-breakout'))
        );
        $this->driver->findElement(WebDriverBy::id('adstats-breakout'))
            ->click();
        sleep(1);

        // Step 2. Select combined report
        $this->logger->debug('Setting combined reports');
        $this->driver->findElement(WebDriverBy::id('adstats-filter-country-both'))
            ->click();

        sleep(1);
        // Step 3. click download
        $downloadLinkContainer = $this->driver->findElement(WebDriverBy::id('section-account-download-adstats'));
        $elements = $downloadLinkContainer->findElements(WebDriverBy::cssSelector('.download-trigger'));
        foreach ($elements as $element) {
            $text = $element->getText();
            if ($text == 'Download') {
                //$element->click();
                $directoryStoreDownloadFile = $this->getDirectoryStoreDownloadFile($startDate, $endDate, $this->getConfig());
                $this->downloadThenWaitUntilComplete($element, $directoryStoreDownloadFile);
                break;
            }
        }
        $this->sleep(2);
    }

    /**
     * @param \DateTime $startDate
     * @param \DateTime $endDate
     * @return $this
     * @throws \Exception
     * @throws \Facebook\WebDriver\Exception\NoSuchElementException
     * @throws \Facebook\WebDriver\Exception\TimeOutException
     * @throws null
     */
    protected function selectDateRange(\DateTime $startDate, \DateTime $endDate)
    {
        $dateWidget = new DateSelectWidget($this->driver);
        $this->sleep(2);
        $dateWidget->setDate($startDate);
        $this->sleep(2);
        $dateWidget->setDate($endDate);

        $this->driver->wait()->until(
            WebDriverExpectedCondition::invisibilityOfElementLocated(WebDriverBy::id('calendar_downloads_account-downloads-adstats'))
        );

        return $this;
    }
}