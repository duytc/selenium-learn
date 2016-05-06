<?php

namespace Tagcade\DataSource\Sovrn\Page;

use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Facebook\WebDriver\WebDriverSelect;
use Tagcade\DataSource\Sovrn\Widget\DateSelectWidget;
use Tagcade\DataSource\PulsePoint\Page\AbstractPage;

class EarningPage extends AbstractPage
{
    const URL = 'https://meridian.sovrn.com/#/view/account/my_downloads';

    public function getAllTagReports(\DateTime $startDate, \DateTime $endDate)
    {

        // popup calendar
        $dateCalendarContainer = $this->driver->findElement(WebDriverBy::id('section-account-download-adstats'));
        $elements = $dateCalendarContainer->findElements(WebDriverBy::className('date-range-calendar'));

        $this->logger->info('Popup browser');
        foreach ($elements as $e) {
            $att = $e->getAttribute('data-rangetype');
            if ($att == 'start') {
                $e->click();
                break;
            }
        }

        $this->driver->wait()->until(
            WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::id('calendar_downloads_account-downloads-adstats'))
        );

        $this->logger->info(sprintf('Setting start date %s', $startDate->format('Y-m-d')));

        // Step 1. Set date range
        $this->selectDateRange($startDate, $endDate);

        $this->driver->wait()->until(
            WebDriverExpectedCondition::invisibilityOfElementLocated(WebDriverBy::id('calendar_downloads_account-downloads-adstats'))
        );
        
        usleep(100);
        // breakdown by day
        $this->logger->info('Setting breakdown by days');
        $this->driver->findElement(WebDriverBy::id('adstats-breakout'))
            ->click()
        ;

        // Step 2. Select combined report
        $this->logger->info('Setting combined reports');
        $this->driver->findElement(WebDriverBy::id('adstats-filter-country-both'))
            ->click()
        ;

        usleep(100);
        // Step 3. click download
        $downloadLinkContainer = $this->driver->findElement(WebDriverBy::id('section-account-download-adstats'));
        $elements = $downloadLinkContainer->findElements(WebDriverBy::cssSelector('.download-trigger'));
        foreach($elements as $element) {
            $text = $element->getText();
            if ($text == 'Download') {
                $element->click();
                break;
            }
        }
    }

    protected function selectDateRange(\DateTime $startDate, \DateTime $endDate)
    {
        $dateWidget = new DateSelectWidget($this->driver);
        $dateWidget->setDate($startDate);
        $dateWidget->setDate($endDate);

        $this->driver->wait()->until(
            WebDriverExpectedCondition::invisibilityOfElementLocated(WebDriverBy::id('calendar_downloads_account-downloads-adstats'))
        );

        return $this;
    }
} 