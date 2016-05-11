<?php

namespace Tagcade\DataSource\Across33\Page;

use DateTime;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverElement;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Facebook\WebDriver\WebDriverSelect;
use Tagcade\DataSource\DefyMedia\Widget\DateSelectWidget;
use Tagcade\DataSource\PulsePoint\Page\AbstractPage;

class DeliveryReportPage extends AbstractPage
{
    const URL = 'http://www.tynt.com/reports';

    const URL_DELIVERY = 'http://www.tynt.com/reports/delivery';

    public function getAllTagReports(\DateTime $startDate, \DateTime $endDate)
    {
        // step 0. select filter
        $this->info('select filter');
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
            $this->info(sprintf('downloading report for domain %s', $domain));

            $this->getAllTagReportsForSingleDomainByDate($startDate, $endDate);

            usleep(500);
        }
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

        sleep(20); // download delay //TODO verify the location of downloaded file
    }

    protected function getAllTagReportsForSingleDomainByOptions(array $optionToClicks, WebDriverSelect $selectElement )
    {

        /** @var WebDriverElement $optionToClick */
        foreach($optionToClicks as $optionToClick) {

            $selectElement->selectByValue($optionToClick->getAttribute('value'));

            $this->driver->findElement(WebDriverBy::cssSelector('#filter_button+span'))
                ->click();;

            $this->driver->wait()->until(
                WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::id('download_current'))
            );

            $this->driver->findElement(WebDriverBy::id('download_current'))
                ->click();;

            sleep(20); //wait for download data
        }
    }

    protected function getAllTagReportsForSingleDomainByDate(\DateTime $startDate, \DateTime $endDate)
    {
       // get range date value
        $selectElement =  new WebDriverSelect ($this->driver->findElement(WebDriverBy::id('begin_date')));
        $selectedOptions = $selectElement->getOptions();

        /** @var WebDriverElement[]  $optionToClicks */
        $optionToClicks =[];
        foreach ($selectedOptions as $selectedOption) {

            $dateValue = $selectedOption->getAttribute('value');
            $reportDate = DateTime::createFromFormat('Y-m-d', $dateValue);
            if (!$reportDate instanceof \DateTime) {
                $this->logger->info(sprintf('Not a valid date format. Expect Y-m-d, found %s', $dateValue));
                continue;
            }

            $monthReportDate = $reportDate->format('n');
            $monthStartDate = $startDate->format('n');

            if ($monthReportDate >= $monthStartDate) {
                $optionToClicks[] = $selectedOption;
            }
        }

        if (count($optionToClicks) == 0) {
            throw new \Exception('Invalidate started date');
        }

        $downloadAllData = false;
        if (count($optionToClicks) === count($selectedOptions)) {
            $downloadAllData = true;
        }

        if ($downloadAllData == true) {
            $this->getAllTagReportsForSingleDomain();
        } else {
            $this->getAllTagReportsForSingleDomainByOptions($optionToClicks,$selectElement);
        }

    }

    protected function selectDateRange(\DateTime $startDate, \DateTime $endDate)
    {
        $dateWidget = new DateSelectWidget($this->driver);
        $dateWidget->setDateRange($startDate, $endDate);
        return $this;
    }
} 