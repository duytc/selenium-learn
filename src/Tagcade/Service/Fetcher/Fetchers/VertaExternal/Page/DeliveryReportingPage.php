<?php

namespace Tagcade\Service\Fetcher\Fetchers\VertaExternal\Page;

use Facebook\WebDriver\Remote\RemoteWebElement;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Tagcade\Service\Fetcher\Fetchers\VertaExternal\Util\VertaExternalUtil;
use Tagcade\Service\Fetcher\Pages\AbstractPage;
use Tagcade\Service\Fetcher\Params\PartnerParamInterface;
use Tagcade\Service\Fetcher\Params\VertaExternal\VertaExternalPartnerParamInterface;

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

    const REPORT_TEMPLATE = "https://ssp.vertamedia.com/pages/statistics/?outstream=all&tabId=Default&%s&%s&%s";

    const SLICES = [
        self::SLICE_CAMPAIGNS => "campaign=all&report=campaigns",
        self::SLICE_DATE => "report=days",
        self::SLICE_AIDS => "source=all&report=aid",
        self::SLICE_COUNTRIES => "country=all&report=countries",
        self::SLICE_DOMAINS => "domain=all&report=domains",
        self::SLICE_USER_AGENTS => "useragent=all&report=useragents",
        self::SLICE_APP_NAME => "app_name=all&report=app_names",
        self::SLICE_APP_BUNDLE_ID => "app_bundle=all&report=app_bundles",
    ];

    const SUPPORT_SLICES = [
        self::REPORT_TYPE_ALL => [self::SLICE_CAMPAIGNS, self::SLICE_DATE, self::SLICE_AIDS, self::SLICE_COUNTRIES],
        self::REPORT_TYPE_DESKTOP => [self::SLICE_CAMPAIGNS, self::SLICE_DATE, self::SLICE_AIDS, self::SLICE_COUNTRIES, self::SLICE_DOMAINS, self::SLICE_USER_AGENTS],
        self::REPORT_TYPE_MOBILE_APP => [self::SLICE_CAMPAIGNS, self::SLICE_DATE, self::SLICE_AIDS, self::SLICE_COUNTRIES, self::SLICE_APP_NAME, self::SLICE_APP_BUNDLE_ID],
        self::REPORT_TYPE_MOBILE_WEB => [self::SLICE_CAMPAIGNS, self::SLICE_DATE, self::SLICE_AIDS, self::SLICE_COUNTRIES, self::SLICE_DOMAINS],
        self::REPORT_TYPE_CTV => [self::SLICE_CAMPAIGNS, self::SLICE_DATE, self::SLICE_AIDS, self::SLICE_COUNTRIES, self::SLICE_APP_NAME, self::SLICE_APP_BUNDLE_ID],
    ];

    const ENVIRONMENTS = [
        self::REPORT_TYPE_ALL => "all",
        self::REPORT_TYPE_DESKTOP => "desktop",
        self::REPORT_TYPE_MOBILE_APP => "mobile_app",
        self::REPORT_TYPE_MOBILE_WEB => "mobile_web",
        self::REPORT_TYPE_CTV => "smarttv",
    ];


    CONST RUN_REPORT_IDS = [1025, 1071];

    public function getAllTagReports(PartnerParamInterface $params)
    {
        if (!$params instanceof VertaExternalPartnerParamInterface) {
            return;
        }

        $this->validateParams($params);

        $reportType = $this->selectReportType($params->getReportType(), $params->getDataSourceId());
        $dateRange = $this->selectDateRanges($params->getStartDate(), $params->getEndDate());
        $slice = $this->selectSlice($params->getSlice(), $params->getDataSourceId());

        $url = sprintf(self::REPORT_TEMPLATE, $reportType, $dateRange, $slice);

        $this->driver->navigate()->to($url);

        $this->driver->wait()->until(WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::className('x-treelist-row')));

        $this->sleep(8);

        $this->runReportAndDownload();

        /** Verta host need more time to return download stream for large report (by DOMAINS), so we wait */
        if ($params->getSlice() == 'DOMAINS') {
            $this->sleep(18);
        }
    }


    /**
     * @param $reportType
     * @param null $dataSourceId
     * @return string
     * @throws \Exception
     */
    private function selectReportType($reportType, $dataSourceId = null)
    {
        $this->logger->debug(sprintf("Report type: %s", $reportType));

        if (array_key_exists($reportType, self::ENVIRONMENTS)) {
            return sprintf('environment=%s', self::ENVIRONMENTS[$reportType]);
        }

        throw new \Exception(sprintf("ReportType %s not correct. Need recheck on Data Source id = %s", $reportType, $dataSourceId));
    }


    /**
     * @param \DateTime $startDate
     * @param \DateTime $endDate
     * @return string
     * @throws \Exception
     */
    protected function selectDateRanges(\DateTime $startDate, \DateTime $endDate)
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
    private function selectSlice($slice, $dataSourceId = null)
    {
        $this->logger->debug(sprintf("Slice: %s", $slice));

        if (array_key_exists($slice, self::SLICES)) {
            return self::SLICES[$slice];
        }

        throw new \Exception(sprintf("Slice %s not correct. Need recheck on Data Source id = %s", $slice, $dataSourceId));
    }

    /**
     * @throws \Exception
     */
    private function runReportAndDownload()
    {
        $isClick = false;
        foreach (self::RUN_REPORT_IDS as $id) {
            try {
                $exportTabId = VertaExternalUtil::getId(VertaExternalUtil::BUTTON, $id);
                $exportTab = $this->driver->findElement(WebDriverBy::id($exportTabId));

                if ($exportTab instanceof RemoteWebElement) {
                    $exportTab->click();
                    $isClick = true;
                    break;
                }
            } catch (\Exception $e) {
            }
        }

        if (!$isClick) {
            throw new \Exception("Id of Download button is not correct. Need recheck code base");
        }

        $exportToCSVButton = $this->filterElementByTagNameAndText('span', "Export to .CSV");
        if ($exportToCSVButton) {
            $exportToCSVButton->click();
            $this->logger->debug('Click download report');
            return;
        }
    }

    /**
     * @param VertaExternalPartnerParamInterface $params
     * @throws \Exception
     */
    private function validateParams(VertaExternalPartnerParamInterface $params)
    {
        $slice = $params->getSlice();
        $reportType = $params->getReportType();

        if (!in_array($slice, self::SUPPORT_SLICES[$reportType])) {
            /** @var PartnerParamInterface $params */
            $dataSourceId = $params->getDataSourceId();
            throw new \Exception(sprintf("ReportType %s do not support Slice %s. Need recheck on Data Source id = %s", $reportType, $slice, $dataSourceId));
        }
    }
}