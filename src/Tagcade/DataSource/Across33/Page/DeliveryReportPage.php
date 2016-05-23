<?php

namespace Tagcade\DataSource\Across33\Page;

use DateTime;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverElement;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Facebook\WebDriver\WebDriverSelect;
use Tagcade\DataSource\DefyMedia\Widget\DateSelectWidget;
use Tagcade\DataSource\PulsePoint\Page\AbstractPage;

class DeliveryReportPage extends AbstractPage
{
    const URL = 'https://platform.33across.com/reports';

    const URL_DELIVERY = 'https://platform.33across.com/reports/delivery';

    public function getAllTagReports(\DateTime $startDate, \DateTime $endDate)
    {
        // step 0. select filter
        $this->info('select filter');
        $links = $this->driver->findElements(WebDriverBy::cssSelector('td a'));
        $reportLinks = [];

        foreach ($links as $link) {
            $href = $link->getAttribute('href');
            $pos = strpos($href, '/reports?sws');
            if ($pos == false) {
                continue;
            }
            $reportLinks[$link->getText()] = $href;
        }

        foreach ($reportLinks as $domain => $l) {
            $deliveryReportLink = str_replace(self::URL, self::URL_DELIVERY, $l);
            $this->driver->navigate()->to($deliveryReportLink);

            $this->driver->wait()->until(
                WebDriverExpectedCondition::titleContains('Delivery Report')
            );

            $this->driver->wait()->until(
                WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::id('deliveryTable'))
            );

            usleep(500);
            $this->info(sprintf('downloading report for domain %s', $domain));
            $this->getAllTagReportsForSingleDomain();
            usleep(500);
        }
    }

    protected function getAllTagReportsForSingleDomain()
    {
        $this->logger->info('Clicking option to select download all data');
        $this->driver->findElement(WebDriverBy::cssSelector('#filter_button+span > a'))
            ->click();
        ;

        $this->logger->info('Wait for download all data menu appeared');
        $this->driver->wait()->until(
            WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::id('download_all_data'))
        );

        $this->logger->info('click download all data');

        /** RemoveWebDriver $downloadElement */
        $downloadElement =  $this->driver->findElement(WebDriverBy::id('download_all_data'));

        $this->downloadThenWaitUntilComplete($downloadElement);

    }

    protected function selectDateRange(\DateTime $startDate, \DateTime $endDate)
    {
        $dateWidget = new DateSelectWidget($this->driver);
        $dateWidget->setDateRange($startDate, $endDate);
        return $this;
    }
} 