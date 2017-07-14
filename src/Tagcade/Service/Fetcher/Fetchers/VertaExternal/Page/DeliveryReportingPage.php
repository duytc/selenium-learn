<?php

namespace Tagcade\Service\Fetcher\Fetchers\VertaExternal\Page;

use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Tagcade\Service\Fetcher\Fetchers\VertaExternal\Util\VertaExternalUtil;
use Tagcade\Service\Fetcher\Pages\AbstractPage;
use Tagcade\Service\Fetcher\Params\PartnerParamInterface;
use Tagcade\Service\Fetcher\Params\Verta\VertaPartnerParamInterface;

class DeliveryReportingPage extends AbstractPage
{
    /** Const reportType */
    const REPORT_TYPE_ALL = 'All';
    const REPORT_TYPE_DESKTOP = 'Desktop';
    const REPORT_TYPE_MOBILE_APP = 'Mobile App';
    const REPORT_TYPE_MOBILE_WEB = 'Mobile Web';
    const REPORT_TYPE_CTV = 'CTV';

    /** Const slice */
    const SLICE_CAMPAIGNS = 'CAMPAIGNS';
    const SLICE_DATE = 'DATE';
    const SLICE_AIDS = 'AIDS';
    const SLICE_COUNTRIES = 'COUNTRIES';
    const SLICE_DOMAINS = 'DOMAINS';
    const SLICE_USER_AGENTS = 'USER AGENTS';
    const SLICE_APP_NAME = 'APP NAME';
    const SLICE_APP_BUNDLE_ID = 'APP BUNDLE ID';

    const URL = 'https://ssp.vertamedia.com/pages/reports';

    public $reportTemplate = "https://ssp.vertamedia.com/pages/statistics/?outstream=all&tabId=Default&%s&%s&%s";

    public $slices = [
        self::SLICE_CAMPAIGNS => "campaign=all&report=campaigns",
        self::SLICE_DATE => "report=days",
        self::SLICE_AIDS => "source=all&report=aid",
        self::SLICE_COUNTRIES => "country=all&report=countries",
        self::SLICE_DOMAINS => "domain=all&report=domains",
        self::SLICE_USER_AGENTS => "useragent=all&report=useragents",
        self::SLICE_APP_NAME => "app_name=all&report=app_names",
        self::SLICE_APP_BUNDLE_ID => "app_bundle=all&report=app_bundles",
    ];

    public $supportSlices = [
        self::REPORT_TYPE_ALL => [self::SLICE_CAMPAIGNS, self::SLICE_DATE, self::SLICE_AIDS, self::SLICE_COUNTRIES],
        self::REPORT_TYPE_DESKTOP => [self::SLICE_CAMPAIGNS, self::SLICE_DATE, self::SLICE_AIDS, self::SLICE_COUNTRIES, self::SLICE_DOMAINS, self::SLICE_USER_AGENTS],
        self::REPORT_TYPE_MOBILE_APP => [self::SLICE_CAMPAIGNS, self::SLICE_DATE, self::SLICE_AIDS, self::SLICE_COUNTRIES, self::SLICE_APP_NAME, self::SLICE_APP_BUNDLE_ID],
        self::REPORT_TYPE_MOBILE_WEB => [self::SLICE_CAMPAIGNS, self::SLICE_DATE, self::SLICE_AIDS, self::SLICE_COUNTRIES, self::SLICE_DOMAINS],
        self::REPORT_TYPE_CTV => [self::SLICE_CAMPAIGNS, self::SLICE_DATE, self::SLICE_AIDS, self::SLICE_COUNTRIES, self::SLICE_APP_NAME, self::SLICE_APP_BUNDLE_ID],
    ];

    public $environments = [
        self::REPORT_TYPE_ALL => "all",
        self::REPORT_TYPE_DESKTOP => "desktop",
        self::REPORT_TYPE_MOBILE_APP => "mobile_app",
        self::REPORT_TYPE_MOBILE_WEB => "mobile_web",
        self::REPORT_TYPE_CTV => "smarttv",
    ];


    CONST RUN_REPORT_IDS = [1025, 1071, 1025, 1071];

    public function getAllTagReports(PartnerParamInterface $params)
    {
        if (!($params instanceof VertaPartnerParamInterface)) {
            return;
        }

        $this->validateParams($params);

        $reportType = $this->selectReportType($params->getReportType(), $params->getDataSourceId());
        $dateRange = $this->selectDateRanges($params->getStartDate(), $params->getEndDate());
        $slice = $this->selectSlice($params->getSlice(), $params->getDataSourceId());

        $url = sprintf($this->reportTemplate, $reportType, $dateRange, $slice);

        $this->driver->navigate()->to($url);

        /** A progress circle show on UI with text Loading...
         *  We wait report loading before click Download button
         */
        $this->driver->wait()->until(WebDriverExpectedCondition::invisibilityOfElementLocated(WebDriverBy::className('x-mask-msg-text')));

        $this->logger->debug("Click download button");
        $this->runReportAndDownload();

        /** Verta host need more time to return download stream for large report (by DOMAINS), so we wait */
        if ($params->getSlice() == 'DOMAINS') {
            $this->sleep(18);
        } else {
            $this->sleep(3);
        }
    }


    /**
     * @param $reportType
     * @param null $dataSourceId
     * @return string
     * @throws \Exception
     */
    public function selectReportType($reportType, $dataSourceId = null)
    {
        $this->logger->debug(sprintf("Report type: %s", $reportType));

        if (array_key_exists($reportType, $this->environments)) {
            return sprintf('environment=%s', $this->environments[$reportType]);
        }

        throw new \Exception(sprintf("ReportType %s not correct. Need recheck on Data Source id = %s", $reportType, $dataSourceId));
    }


    /**
     * @param \DateTime $startDate
     * @param \DateTime $endDate
     * @return string
     * @throws \Exception
     */
    public function selectDateRanges(\DateTime $startDate, \DateTime $endDate)
    {
        $this->logger->debug(sprintf("Date range: from %s to %s", $startDate->format("Y-m-d"), $endDate->format("Y-m-d")));

        $dateRange = sprintf('date_from=%s&date_to=%s', $startDate->format('m d Y'), $endDate->format('m d Y'));
        return str_replace(" ", "%2F", $dateRange);
    }


    /**
     * @param $slice
     * @param null $dataSourceId
     * @return string
     * @throws \Exception
     */
    public function selectSlice($slice, $dataSourceId = null)
    {
        $this->logger->debug(sprintf("Slice: %s", $slice));

        if (array_key_exists($slice, $this->slices)) {
            return $this->slices[$slice];
        }

        throw new \Exception(sprintf("Slice %s not correct. Need recheck on Data Source id = %s", $slice, $dataSourceId));
    }

    /**
     * @throws \Exception
     */
    public function runReportAndDownload()
    {
        $allElement = $this->filterElementByTagNameAndText('label', 'All');
        if (!$allElement) {
            $this->runReportAndDownload();
        }

        $isClick = false;
        foreach (self::RUN_REPORT_IDS as $id) {
            try {
                $exportTabId = VertaExternalUtil::getId(VertaExternalUtil::BUTTON, $id);
                $exportTab = $this->driver->findElement(WebDriverBy::id($exportTabId));

                if ($exportTab) {
                    try {
                        $exportTab->click();
                        $isClick = true;
                        break;
                    } catch (\Exception $e) {

                    }
                }
            } catch (\Exception $e) {
            }
        }

        if (!$isClick) {
            throw new \Exception("Id of Download button is not correct. Need recheck code base");
        }

        try {
            $this->clickExportToCSV();
        } catch (\Exception $e) {
            $this->clickExportToCSV();
        }
    }

    /**
     * @param VertaPartnerParamInterface $params
     * @throws \Exception
     */
    public function validateParams(VertaPartnerParamInterface $params)
    {
        $slice = $params->getSlice();
        $reportType = $params->getReportType();

        if (!in_array($slice, $this->supportSlices[$reportType])) {
            /** @var PartnerParamInterface $params */
            $dataSourceId = $params->getDataSourceId();
            throw new \Exception(sprintf("ReportType %s do not support Slice %s. Need recheck on Data Source id = %s", $reportType, $slice, $dataSourceId));
        }
    }

    public function clickExportToCSV()
    {
        $exportToCSVButton = $this->filterElementByTagNameAndText('span', "Export to .CSV");
        if ($exportToCSVButton) {
            $exportToCSVButton->click();
            $this->logger->debug('Click download report');
            return;
        }
    }
}