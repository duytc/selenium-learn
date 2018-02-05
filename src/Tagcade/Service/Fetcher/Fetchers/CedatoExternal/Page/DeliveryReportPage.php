<?php

namespace Tagcade\Service\Fetcher\Fetchers\CedatoExternal\Page;

use DateTime;
use Exception;
use Facebook\WebDriver\Exception\NoSuchElementException;
use Facebook\WebDriver\Exception\TimeOutException;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Tagcade\Service\Fetcher\Fetchers\Gamut\Widget\DateSelectWidget;
use Tagcade\Service\Fetcher\Pages\AbstractPage;

class DeliveryReportPage extends AbstractPage
{
    const URL = 'https://dashboard.cedato.com/#/players/reports/0/0/';
    const SUPPLY_REPORT_TYPE = 'supply';
    const SUPPLY_BY_DEMAND_SOURCES_REPORT_TYPE = 'supply by demand sources';
    const DEMAND_SOURCE_BY_SUPPLY_REPORT_TYPE = 'demand sources by supply';
    const DEMAND_REPORT_TYPE = 'demand';
    const DOMAIN_REPORT_TYPE = 'domains';

    const SUPPLY_URL = 'https://dashboard.cedato.com/#/players/reports/0/0/0/';
    const SUPPLY_BY_DEMAND_SOURCES_URL = 'https://dashboard.cedato.com/#/players/report/ByVast/all/0/0/0/';
    const DEMAND_SOURCE_BY_SUPPLY_URL = 'https://dashboard.cedato.com/#/vasts/report/ByPlayer/all/0/0/0/';
    const DEMAND_URL = 'https://dashboard.cedato.com/#/vasts/reports/0/0/0';
    const DOMAIN_URL = 'https://dashboard.cedato.com/#/reports/domains/all/0/0/0';
    const LOG_OUT_URL = 'https://dashboard.cedato.com/#/login';

    /**
     * @param DateTime $startDate
     * @param DateTime $endDate
     * @throws Exception
     * @throws NoSuchElementException
     * @throws TimeOutException
     * @throws null
     */
    public function getAllTagReports(\DateTime $startDate, \DateTime $endDate)
    {
        // step 0. select filter
        $this->logger->debug('select filter');
        $this->sleep(2);
        $this->driver->findElement(WebDriverBy::id('daterange'))->click();
        $this->sleep(1);
        $dateRangePiker = $this->driver->findElement(WebDriverBy::className('daterangepicker'));
        $ranges = $dateRangePiker->findElement(WebDriverBy::className('ranges'));
        $selectList = $ranges->findElements(WebDriverBy::tagName('li'));
        foreach ($selectList as $item) {
            if (strcmp(trim($item->getText()), 'Custom Range') === 0) {
                $item->click();
                break;
            }
        }

        $this->sleep(1);

        /*
         * select date
         */
        $startDateString = $startDate->format('Y-m-d');
        $endDateString = $endDate->format('Y-m-d');
        $startDateElement = $dateRangePiker->findElement(WebDriverBy::name("daterangepicker_start"));
        $startDateElement->clear();
        $startDateElement->sendKeys($startDateString);
        $endDateElement = $dateRangePiker->findElement(WebDriverBy::name("daterangepicker_end"));
        $endDateElement->clear();
        $endDateElement->sendKeys($endDateString);
        $this->sleep(1);

        /*
         * click apply
         */
        $rangeInput = $ranges->findElement(WebDriverBy::className('range_inputs'));
        $rangeInput->findElement(WebDriverBy::className('applyBtn'))->click();
        $this->sleep(1);

        try {
            $this->driver->wait()->until(WebDriverExpectedCondition::presenceOfAllElementsLocatedBy(WebDriverBy::className('ui-grid-menu-button')));
            $grid_menu = $this->driver->findElement(WebDriverBy::className('ui-grid-menu-button'));
        } catch (\Exception $e) {
            $this->driver->wait()->until(WebDriverExpectedCondition::presenceOfAllElementsLocatedBy(WebDriverBy::className('ui-grid-icon-menu')));
            $grid_menu = $this->driver->findElement(WebDriverBy::className('ui-grid-icon-menu'));
        }

        $grid_menu->click();
        $this->sleep(1);

        $this->driver->wait()->until(WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::id('menuitem-1')));
        $this->getAllTagReportsForSingleDomain($startDate, $endDate);
    }

    /**
     * @param DateTime $startDate
     * @param DateTime $endDate
     */
    protected function getAllTagReportsForSingleDomain(\DateTime $startDate, \DateTime $endDate)
    {
        $exportAllAsCsv = $this->driver->findElement(WebDriverBy::id('menuitem-1'));
        $this->sleep(1);

        $this->downloadFileHelper->downloadThenWaitUntilComplete($exportAllAsCsv, $this->getDirectoryStoreDownloadFile($startDate, $endDate, $this->getConfig()));
    }

    /**
     * @param DateTime $startDate
     * @param DateTime $endDate
     * @return $this
     */
    protected function selectDateRange(\DateTime $startDate, \DateTime $endDate)
    {
        $dateWidget = new DateSelectWidget($this->driver);
        $dateWidget->setDateRange($startDate, $endDate);
        return $this;
    }

    /**
     * navigate to ReportPage based on report type
     * @param string $reportType
     * @throws Exception
     */
    public function navigateToReportPage($reportType)
    {
        switch (strtolower($reportType)) {
            case self::DEMAND_REPORT_TYPE:
                $url = self::DEMAND_URL;
                break;
            case self::DEMAND_SOURCE_BY_SUPPLY_REPORT_TYPE:
                $url = self::DEMAND_SOURCE_BY_SUPPLY_URL;
                break;
            case self::SUPPLY_REPORT_TYPE:
                $url = self::SUPPLY_URL;
                break;
            case self::SUPPLY_BY_DEMAND_SOURCES_REPORT_TYPE:
                $url = self::SUPPLY_BY_DEMAND_SOURCES_URL;
                break;
            default:
                $this->logger->warning(sprintf('cannot find report type: %s', $reportType));
                throw new Exception(sprintf('cannot find report type: %s', $reportType));
        }

        $this->driver->navigate()->to($url);
    }
}