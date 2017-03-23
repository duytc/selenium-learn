<?php

namespace Tagcade\Service\Fetcher\Fetchers\Gamut\Page;

use DateTime;
use Facebook\WebDriver\Exception\NoSuchElementException;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Facebook\WebDriver\WebDriverSelect;
use PHPExcel_IOFactory;
use Tagcade\Service\Fetcher\Fetchers\Gamut\Widget\DateSelectWidget;
use Tagcade\Service\Fetcher\Fetchers\PulsePoint\Page\AbstractPage;

class DeliveryReportPage extends AbstractPage
{
    const URL = 'http://app-1.gamut.media/MemberPages/Reports/Editor.aspx';

    public function getAllTagReports(\DateTime $startDate, \DateTime $endDate)
    {
        // step 0. select filter
        $this->logger->debug('select filter');
        $this->sleep(2);
        $this->driver->wait()->until(WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::id('ctl00_ctl00_PageLayoutBody_BodyContent_ViewReport1')));

        $reportType = $this->driver->findElement(WebDriverBy::id('ctl00_ctl00_PageLayoutBody_BodyContent_ListReportType'));
        $reportTypeSelect = new WebDriverSelect($reportType);
        $reportTypeSelect->selectByValue('PublisherPerformanceReport');
        $this->sleep(1);

        $forOneOrMore = $this->driver->findElement(WebDriverBy::id('ctl00_ctl00_PageLayoutBody_BodyContent_ListPublisherPerformanceReportRollups'));
        $forOneOrMoreSelect = new WebDriverSelect($forOneOrMore);
        $forOneOrMoreSelect->selectByValue('PublisherPerformance-Site');
        $this->sleep(1);

        $this->selectDateRange($startDate, $endDate);
        $this->sleep(1);

        $timeZone = $this->driver->findElement(WebDriverBy::id('ctl00_ctl00_PageLayoutBody_BodyContent_TimeZoneDropDown'));
        $timeZoneSelect = new WebDriverSelect($timeZone);
        $timeZoneSelect->selectByValue('GMT');
        $this->sleep(1);

        $this->driver->findElement(WebDriverBy::id('ctl00_ctl00_PageLayoutBody_BodyContent_ViewReport1'))->click();
        $this->sleep(2);

        $this->driver->wait()->until(WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::id('ctl00_ctl00_PageLayoutBody_BodyContent_divDateRangeStatement')));

        try {
            $this->driver->findElement(WebDriverBy::id('ctl00_ctl00_PageLayoutBody_BodyContent_ReportJSGrid_Table'));
            $this->getAllTagReportsForSingleDomain($startDate, $endDate);
        } catch (NoSuchElementException $noSuchElementException) {
            $noDataError = $this->driver->findElement(WebDriverBy::id('ctl00_ctl00_PageLayoutBody_BodyContent_dateSelector_valiationLabel'));
            $this->logger->error($noDataError->getText());
        }


        $this->logger->debug('Logout system');
        $this->driver->findElement(WebDriverBy::id('ctl00_ctl00_appHeader_signOutLinkButton'))->click();
    }

    /**
     * @param DateTime $startDate
     * @param DateTime $endDate
     * @throws \Exception
     * @throws \Facebook\WebDriver\Exception\NoSuchElementException
     * @throws \Facebook\WebDriver\Exception\TimeOutException
     * @throws null
     * @internal param DateTime $startDatem
     */
    protected function getAllTagReportsForSingleDomain(\DateTime $startDate, \DateTime $endDate)
    {
        $tableReport = $this->driver->findElement(WebDriverBy::id('ctl00_ctl00_PageLayoutBody_BodyContent_ReportJSGrid_Table'));
        $links = $tableReport->findElements(WebDriverBy::cssSelector('td a'));
        $numOfLink = count($links);

        for ($i = 0; $i < $numOfLink; $i++) {
            /*
             * get all links again after go back
             */
            $tableReport = $this->driver->findElement(WebDriverBy::id('ctl00_ctl00_PageLayoutBody_BodyContent_ReportJSGrid_Table'));
            $this->driver->wait()->until(WebDriverExpectedCondition::presenceOfAllElementsLocatedBy(WebDriverBy::cssSelector('td a')));
            $reportLinks = $tableReport->findElements(WebDriverBy::cssSelector('td a'));
            $link = $reportLinks[$i];
            $link->click();

            $this->driver->wait()->until(WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::id('ctl00_ctl00_PageLayoutBody_BodyContent_lbCurrentReport')));
            $this->sleep(2);
            try {
                /*
                 * if no data then continue
                 */
                $htmlTable = $this->driver->findElement(WebDriverBy::id('ctl00_ctl00_PageLayoutBody_BodyContent_ReportJSGrid_Table'))->getAttribute("innerHTML");
            } catch (NoSuchElementException $noSuchElementException) {
                $this->driver->navigate()->back();
                continue;
            }

            // save $table inside temporary file that will be deleted later
            $tmpFile = tempnam(sys_get_temp_dir(), 'html');
            file_put_contents($tmpFile, $htmlTable);

            $objReader = PHPExcel_IOFactory::createReader('HTML');
            $objPHPExcel = $objReader->load($tmpFile);

            $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
            $directoryStoreDownloadFile = $this->getDirectoryStoreDownloadFile($startDate, $endDate, $this->getConfig());
            $objWriter->save(sprintf('%s/%s-%s-%s(%d).xlsx', $directoryStoreDownloadFile, 'gamut', $startDate->format('Y-m-d'), $endDate->format('Y-m-d'), $i + 1));


            $this->sleep(1);
            $this->driver->navigate()->back();
        }
    }

    protected function selectDateRange(\DateTime $startDate, \DateTime $endDate)
    {
        $dateWidget = new DateSelectWidget($this->driver);
        $dateWidget->setDateRange($startDate, $endDate);
        return $this;
    }

    protected function logOutSystem()
    {
        $logOutButtonCss = '#main-nav > ul > li.logout > a';
        $this->driver->findElement(WebDriverBy::cssSelector($logOutButtonCss))->click();
    }
} 