<?php

namespace Tagcade\DataSource\Across33\Page;

use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Tagcade\DataSource\DefyMedia\Widget\DateSelectWidget;
use Tagcade\DataSource\PulsePoint\Page\AbstractPage;

class DeliveryReportPage extends AbstractPage
{
    const URL = 'http://www.tynt.com/reports';

    const URL_DELIVERY = 'http://www.tynt.com/reports/delivery';

    public function getAllTagReports(\DateTime $startDate, \DateTime $endDate)
    {
        // step 0. select filter
        $links = $this->driver->findElements(WebDriverBy::cssSelector('#overview-stats a'));
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

            $this->getAllTagReportsForSingleDomain();
            usleep(500);
        }

//        $this->driver->wait()->until(
//            WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::id('report-date'))
//        );
//        // show report date selection
//        $this->driver->findElement(WebDriverBy::id('report-date'))
//            ->click()
//        ;
//
//        $this->driver->wait()->until(
//            WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::cssSelector('.daterangepicker'))
//        );
//
//
//        // Step 1. Select date range
//        $this->selectDateRange($startDate, $endDate);
//        $this->driver->findElement(WebDriverBy::id('report-export'))
//            ->click()
//        ;

    }

    protected function getAllTagReportsForSingleDomain()
    {
        $this->driver->findElement(WebDriverBy::cssSelector('#filter_button+span'))
            ->click();
        ;

        $this->driver->wait()->until(
            WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::id('download_all_data'))
        );

        $this->driver->findElement(WebDriverBy::id('download_all_data'))
            ->click();
        ;

        sleep(12); // download delay //TODO verify the location of downloaded file
    }

    protected function selectDateRange(\DateTime $startDate, \DateTime $endDate)
    {
        $dateWidget = new DateSelectWidget($this->driver);
        $dateWidget->setDateRange($startDate, $endDate);
        return $this;
    }
} 