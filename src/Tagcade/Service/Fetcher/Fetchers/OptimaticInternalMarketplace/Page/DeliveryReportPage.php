<?php

namespace Tagcade\Service\Fetcher\Fetchers\OptimaticInternalMarketplace\Page;

use DateTime;
use Exception;
use Facebook\WebDriver\Exception\NoSuchElementException;
use Facebook\WebDriver\Exception\TimeOutException;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Tagcade\Service\Fetcher\Fetchers\Gamut\Widget\DateSelectWidget;
use Tagcade\Service\Fetcher\Pages\AbstractPage;
use Tagcade\Service\Fetcher\Params\OptimaticInternalMarketplace\OptimaticInternalMarketplacePartnerParamsInterface;
use Tagcade\Service\Fetcher\Params\PartnerParamInterface;

class DeliveryReportPage extends AbstractPage
{
    const URL = 'https://publishers.optimatic.com/Portal2/default.aspx';
    const TREND_BY_PLACEMENT_REPORT_TYPE = 'trend by placement';
    const PAY_MY_PARTNERS_REPORT_TYPE = 'pay my partners';

    const TREND_BY_PLACEMENT_URL = 'https://publishers.optimatic.com/Portal2/reports/SSP/TrendByPlacement.aspx';
    const PAY_MY_PARTNERS_URL = 'https://publishers.optimatic.com/Portal2/reports/SSP/PayMyPartners.aspx';

    const LOG_OUT_URL = 'https://publishers.optimatic.com/Portal2/Logout.aspx';

    public function getAllTagReports(OptimaticInternalMarketplacePartnerParamsInterface $params)
    {
        if (!$params instanceof PartnerParamInterface) {

        }

        if (!$params instanceof OptimaticInternalMarketplacePartnerParamsInterface) {
            throw new Exception('must be optimatic internal marketplace');
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

        $this->sleep(1);

        // select trend option
        $dpPlacementsElement = $this->driver->findElement(WebDriverBy::id("dp_placements"));
        //getText and then compare to defind type report
        $displayText = $dpPlacementsElement->getText();
        $dpPlacementsElement->click();
        if (strpos($displayText, 'Placement')) {
            $paramsOption = $params->getPlacements();
        } else {
            $paramsOption = $params->getPartners();
        }

        $optionElements = $dpPlacementsElement->findElements(WebDriverBy::tagName('option'));
        foreach ($optionElements as $optionElement) {

            if (preg_match($paramsOption, $optionElement->getText())) {
                $dpPlacementsElement->sendKeys($optionElement->getText());
                break;
            }

        }

        $this->driver->findElement(WebDriverBy::id('dp_placements'))->click();

        $this->sleep(1);

        $this->driver->findElement(WebDriverBy::id('ContentPlaceHolder_Body_searchButton'))->click();

        $this->sleep(3);

        try {
            if ($this->driver->findElement(WebDriverBy::cssSelector('body > div.alert > div.container > div.content > div'))){
                $this->logger->notice('No data was found.');

                $this->driver->findElement(WebDriverBy::cssSelector('body > div.alert > div.container > div.ok'))->click();
            }
        } catch (Exception $ex) {

        }

        $this->driver->findElement(WebDriverBy::cssSelector('#bottom-block > button'))->click();

        $this->sleep(10);

    }

    /**
     * @param DateTime $startDate
     * @param DateTime $endDate
     * @throws Exception
     * @throws NoSuchElementException
     * @throws TimeOutException
     * @throws null
     * @internal param DateTime $startDatem
     */
    protected function getAllTagReportsForSingleDomain(\DateTime $startDate, \DateTime $endDate)
    {
        $exportAllAsCsv = $this->driver->findElement(WebDriverBy::id('menuitem-1'));
        $this->sleep(1);

        $this->downloadFileHelper->downloadThenWaitUntilComplete($exportAllAsCsv, $this->getDirectoryStoreDownloadFile($startDate, $endDate, $this->getConfig()));
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
            case self::TREND_BY_PLACEMENT_REPORT_TYPE:
                $url = self::TREND_BY_PLACEMENT_URL;
                break;

            case self::PAY_MY_PARTNERS_REPORT_TYPE:
                $url = self::PAY_MY_PARTNERS_URL;
                break;

            default:
                $this->logger->notice(sprintf('cannot find report type: %s', $reportType));
                throw new Exception(sprintf('cannot find report type: %s', $reportType));
        }

        $this->driver->navigate()->to($url);
    }
}