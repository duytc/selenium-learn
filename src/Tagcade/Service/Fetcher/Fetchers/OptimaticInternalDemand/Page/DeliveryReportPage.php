<?php

namespace Tagcade\Service\Fetcher\Fetchers\OptimaticInternalDemand\Page;

use DateTime;
use Exception;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use PHPExcel_IOFactory;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Tagcade\Service\Fetcher\Fetchers\Gamut\Widget\DateSelectWidget;
use Tagcade\Service\Fetcher\Pages\AbstractPage;
use Tagcade\Service\Fetcher\Params\OptimaticInternalDemand\OptimaticInternalDemandPartnerParamsInterface;
use Tagcade\Service\Fetcher\Params\PartnerParamInterface;
use Tagcade\Service\WebDriverService;

class DeliveryReportPage extends AbstractPage
{
    const URL = 'https://publishers.optimatic.com/Portal2/default.aspx';
    const REVENUE_BY_ADVERTISER_REPORT_TYPE = 'revenue by advertiser';
    const AD_SOURCE_REPORT_REPORT_TYPE = 'ad source report';
    const DOMAIN_BY_AD_SOURCE_REPORT_TYPE = 'domain by ad source';
    const TREND_BY_ADVERTISER_REPORT_TYPE = 'trend by advertiser';

    const REVENUE_BY_ADVERTISER_URL = 'https://publishers.optimatic.com/Portal2/reports/SSP/RevenueByAdvertiser.aspx';
    const AD_SOURCE_REPORT_URL = 'https://publishers.optimatic.com/Portal2/reports/SSP/AdSourceReport.aspx';
    const DOMAIN_BY_AD_SOURCE_URL = 'https://publishers.optimatic.com/Portal2/reports/SSP/DomainByAdSource.aspx';
    const TREND_BY_ADVERTISER_URL = 'https://publishers.optimatic.com/Portal2/NetworkOverview/TREND/TrendbyAdvertiser.aspx?s=demand';

    const LOG_OUT_URL = 'https://publishers.optimatic.com/Portal2/Logout.aspx';

    /**
     * @param OptimaticInternalDemandPartnerParamsInterface $params
     * @throws Exception
     */
    public function getAllTagReports(OptimaticInternalDemandPartnerParamsInterface $params)
    {
        if (!$params instanceof PartnerParamInterface) {

        }

        if (!$params instanceof OptimaticInternalDemandPartnerParamsInterface) {
            throw new Exception('must be optimatic internal demand');
        }

        // step 0. select filter
        $this->logger->debug('select filter');

        $dateRangeElement = $this->driver->findElement(WebDriverBy::id('ContentPlaceHolder_Body_dp_datesearch'))->click();
        $dateRangeElement->sendKeys('CUSTOM_DATE');
        $this->driver->findElement(WebDriverBy::id('ContentPlaceHolder_Body_dp_datesearch'))->click();
        $this->sleep(1);

        $this->logger->debug('Wait until appear Start and End date');
        $this->driver->wait()->until(WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::id('ContentPlaceHolder_Body_txtStartDate')));
        /*
         * select date
         */
        $startDateString = $params->getStartDate()->format('m/d/Y');
        $endDateString = $params->getEndDate()->format('m/d/Y');

        $startDateElement = $this->driver->findElement(WebDriverBy::id("ContentPlaceHolder_Body_txtStartDate"));
        $startDateElement->clear();
        $startDateElement->sendKeys($startDateString);

        $endDateElement = $this->driver->findElement(WebDriverBy::id("ContentPlaceHolder_Body_txtEndDate"));
        $endDateElement->clear();
        $endDateElement->sendKeys($endDateString);
        $config = $params->getConfig();
        $this->sleep(1);
        /* Get path download default to create an empty file for no data was found exeception */
//        $defaultPathDownload = $this->downloadFileHelper->getRootDirectory();
//        $defaultDownloadPath = WebDriverService::getDownloadPath(
//            $defaultPathDownload,
//            $params->getPublisherId(),
//            $params->getIntegrationCName(),
//            new DateTime(),
//            $params->getStartDate(),
//            $params->getEndDate(),
//            $this->config['process_id'],
//            null
//        );

        $defaultDownloadPath = $config['defaultDownloadPath'];
        /*
         * For each report type has select option different
         * So have to make function to click select option for each report type
         *
         * Element Id of buttons Search and Download are different for each report type
         * So for each report type will has a function search and download
         * */
        switch (strtolower($params->getReportType())) {
            case self::REVENUE_BY_ADVERTISER_REPORT_TYPE:
                $this->executeClickSearchAndDownload();
                //$this->executeClickSearchAndDownload($defaultDownloadPath);
                break;

            case self::AD_SOURCE_REPORT_REPORT_TYPE:
                $this->executeClickOptionsForAdSourceReportReportType($params->getAdvertiser());
                $this->executeClickSearchAndDownload();
                break;

            case self::DOMAIN_BY_AD_SOURCE_REPORT_TYPE:
                $this->executeClickOptionsForDomainByAdSourceReportType($params->getAdvertiser(), $params->getPlacements());
                $this->executeClickSearchAndDownloadForDomainByAdSource();
                break;

            case self::TREND_BY_ADVERTISER_REPORT_TYPE:
                $this->executeClickOptionsForTrendByAdvertiserReportType($params->getAllTrendByAdv());
                $this->executeClickSearchAndDownloadForTrendByAdvertiser($defaultDownloadPath);
                break;

            default:
                break;
        }

        $this->sleep(10);

    }

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
            case self::REVENUE_BY_ADVERTISER_REPORT_TYPE:
                $url = self::REVENUE_BY_ADVERTISER_URL;
                break;

            case self::AD_SOURCE_REPORT_REPORT_TYPE:
                $url = self::AD_SOURCE_REPORT_URL;
                break;

            case self::DOMAIN_BY_AD_SOURCE_REPORT_TYPE:
                $url = self::DOMAIN_BY_AD_SOURCE_URL;
                break;

            case self::TREND_BY_ADVERTISER_REPORT_TYPE:
                $url = self::TREND_BY_ADVERTISER_URL;
                break;

            default:
                $this->logger->error(sprintf('cannot find report type: %s', $reportType));
                throw new Exception(sprintf('cannot find report type: %s', $reportType));
        }

        $this->driver->navigate()->to($url);
    }

    /**
     * @param $advertiser
     */
    protected function executeClickOptionsForAdSourceReportReportType($advertiser)
    {
        /* Select Advertising */
        $advertiserElement = $this->driver->findElement(WebDriverBy::id("ContentPlaceHolder_Body_dp_Adverister"));
        $advertiserElement->click();

        $optionElements = $advertiserElement->findElements(WebDriverBy::tagName('option'));
        foreach ($optionElements as $optionElement) {

            if (preg_match($advertiser, $optionElement->getText())) {
                $advertiserElement->sendKeys($optionElement->getText());
                break;
            }
        }

        $advertiserElement->click();
        //$this->driver->findElement(WebDriverBy::id('ContentPlaceHolder_Body_dp_Adverister'))->click();
        $this->sleep(1);

    }

    /**
     * @param $advertiser
     * @param $placements
     */
    protected function executeClickOptionsForDomainByAdSourceReportType($advertiser, $placements)
    {
        /* Select Advertising */
        $advertiserElement = $this->driver->findElement(WebDriverBy::id("ContentPlaceHolder_Body_dp_AdvertiserID"));
        $advertiserElement->click();

        $optionElements = $advertiserElement->findElements(WebDriverBy::tagName('option'));
        foreach ($optionElements as $optionElement) {

            if (preg_match($advertiser, $optionElement->getText())) {
                $advertiserElement->sendKeys($optionElement->getText());
                break;
            }
        }

        $advertiserElement->click();
        $this->sleep(2);

        /* Select Placements */
        $placementsElement = $this->driver->findElement(WebDriverBy::id("ContentPlaceHolder_Body_dp_Ad_ID"));
        $placementsElement->click();

        /* make default is All Placements */
        if (isset($placements) && !empty($placements)) {
            $optionElements = $placementsElement->findElements(WebDriverBy::tagName('option'));
            foreach ($optionElements as $optionElement) {

                if (preg_match($placements, $optionElement->getText())) {
                    $placementsElement->sendKeys($optionElement->getText());
                    break;
                }
            }

        } else {
            $placementsElement->sendKeys('All Placements');
        }


        $placementsElement->click();
        $this->sleep(1);
    }

    /**
     * @param $allTrendByAdv
     */
    protected function executeClickOptionsForTrendByAdvertiserReportType($allTrendByAdv)
    {
        /* Select Advertising */
        $allTrendByAdvElement = $this->driver->findElement(WebDriverBy::id("ContentPlaceHolder_Body_dp_Adverister"));
        $allTrendByAdvElement->click();
        if (isset($allTrendByAdv) && !empty($allTrendByAdv)) {
            $optionElements = $allTrendByAdvElement->findElements(WebDriverBy::tagName('option'));
            foreach ($optionElements as $optionElement) {

                if (preg_match($allTrendByAdv, $optionElement->getText())) {
                    $allTrendByAdvElement->sendKeys($optionElement->getText());
                    break;
                }
            }
        } else {
            $allTrendByAdvElement->sendKeys('ALL');
        }

        $allTrendByAdvElement->click();
        $this->sleep(2);
    }

    protected function executeClickSearchAndDownload()
    {

        $this->sleep(1);
        $this->driver->findElement(WebDriverBy::id('ContentPlaceHolder_Body_searchButton'))->click();
        $this->sleep(3);

        $this->executeCatchNoDataWasFound();

        $this->driver->findElement(WebDriverBy::cssSelector('#bottom-block > button'))->click();
    }

    protected function executeClickSearchAndDownloadForDomainByAdSource()
    {

        $this->sleep(1);
        $this->driver->findElement(WebDriverBy::id('ContentPlaceHolder_Body_btnSearch'))->click();
        $this->sleep(3);

        $this->executeCatchNoDataWasFound();

        $this->driver->findElement(WebDriverBy::cssSelector('#ContentPlaceHolder_Body_adSourcereportContainer > div.tableDownload > div.button'))->click();
    }

    /**
     * @param $defaultDownloadPath
     */
    protected function executeClickSearchAndDownloadForTrendByAdvertiser($defaultDownloadPath)
    {

        $this->sleep(1);
        $this->logger->debug('Click Search');
        $this->driver->findElement(WebDriverBy::id('ContentPlaceHolder_Body_searchButton'))->click();
        $this->sleep(3);

        $this->executeCatchNoDataWasFound();
        $this->sleep(3);
        $this->logger->debug('Click download');
        $this->driver->findElement(WebDriverBy::cssSelector('#webform > div.mainContainer > div > div.filter > button'))->click();

        $this->logger->debug('Wait until download process is finished');
        if (!is_dir($defaultDownloadPath)) {
            mkdir($defaultDownloadPath, 0755, true);
        }
        $this->waitDownloadComplete($defaultDownloadPath);

        $this->logger->debug('Convert file from csv to xls');
        $csvFilePath = sprintf('%s/%s', $defaultDownloadPath, 'TrendByAdvertiser.csv');
        $xlsFilePath = sprintf('%s/%s', $defaultDownloadPath, 'TrendByAdvertiser.xls');
        $objReader = PHPExcel_IOFactory::createReader('CSV');
        $objPHPExcel = $objReader->load($csvFilePath);
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save($xlsFilePath);
        $this->sleep(2);
        $this->logger->debug('Delete Csv file');
        unlink($csvFilePath);
        $this->sleep(2);
        $this->logger->debug('Finish convert');
    }

    protected function executeCatchNoDataWasFound()
    {
        try {
            if ($this->driver->findElement(WebDriverBy::cssSelector('body > div.alert > div.container > div.content > div'))) {
                $this->logger->error('No data was found.');

                $this->driver->findElement(WebDriverBy::cssSelector('body > div.alert > div.container > div.ok'))->click();

//                if (!is_dir($defaultDownloadPath)) {
//                    mkdir($defaultDownloadPath, 0755, true);
//                }
//                touch(sprintf('%s/datawasnotfound.csv', $defaultDownloadPath));
                $this->sleep(2);
            }
        } catch (Exception $ex) {

        }
    }

    /**
     * @param $downloadFolder
     */
    private function waitDownloadComplete($downloadFolder)
    {
        // check that chrome finished downloading all files before finishing
        sleep(5);

        do {
            $fileSize1 = $this->getDirSize($downloadFolder);  // check file size
            sleep(5);
            $fileSize2 = $this->getDirSize($downloadFolder);
        } while ($fileSize1 != $fileSize2);

        sleep(3);
    }

    /**
     * @param $directory
     * @return int
     */
    private function getDirSize($directory)
    {
        $size = 0;
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory)) as $file) {
            $size += $file->getSize();
        }
        return $size;
    }
}