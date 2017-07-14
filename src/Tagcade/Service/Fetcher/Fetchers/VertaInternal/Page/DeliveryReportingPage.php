<?php

namespace Tagcade\Service\Fetcher\Fetchers\VertaInternal\Page;

class DeliveryReportingPage extends \Tagcade\Service\Fetcher\Fetchers\VertaExternal\Page\DeliveryReportingPage
{
    public $reportTemplate = "https://ssp.vertamedia.com/pages/reports/?outstream=all&tabId=Default&%s&%s&%s";

    /** Add Constant slice */
    const SLICE_CHANNELS = 'CHANNELS';
    const SLICE_SOURCES = 'SOURCES';
    const SLICE_ADVERTISERS = 'ADVERTISERS';

    public $slices = [
        self::SLICE_DATE => "report=days",
        self::SLICE_CHANNELS => "channel=all&report=publishers",
        self::SLICE_SOURCES => "report=aid",
        self::SLICE_ADVERTISERS => "advertiser=all&report=advertisers",
        self::SLICE_CAMPAIGNS => "report=campaigns",
        self::SLICE_COUNTRIES => "country=all&report=countries",
        self::SLICE_DOMAINS => "domain=all&report=domains",
        self::SLICE_USER_AGENTS => "useragent=all&report=useragents",
        self::SLICE_APP_NAME => "app_name=all&report=app_names",
        self::SLICE_APP_BUNDLE_ID => "app_bundle=all&report=app_bundles",
    ];

    public $supportSlices = [
        self::REPORT_TYPE_ALL => [self::SLICE_DATE, self::SLICE_CHANNELS, self::SLICE_SOURCES, self::SLICE_ADVERTISERS, self::SLICE_CAMPAIGNS, self::SLICE_COUNTRIES],
        self::REPORT_TYPE_DESKTOP => [self::SLICE_DATE, self::SLICE_CHANNELS, self::SLICE_SOURCES, self::SLICE_ADVERTISERS, self::SLICE_CAMPAIGNS, self::SLICE_COUNTRIES, self::SLICE_DOMAINS, self::SLICE_USER_AGENTS],
        self::REPORT_TYPE_MOBILE_APP => [self::SLICE_DATE, self::SLICE_CHANNELS, self::SLICE_SOURCES, self::SLICE_ADVERTISERS, self::SLICE_CAMPAIGNS, self::SLICE_COUNTRIES, self::SLICE_APP_NAME, self::SLICE_APP_BUNDLE_ID],
        self::REPORT_TYPE_MOBILE_WEB => [self::SLICE_DATE, self::SLICE_CHANNELS, self::SLICE_SOURCES, self::SLICE_ADVERTISERS, self::SLICE_CAMPAIGNS, self::SLICE_COUNTRIES, self::SLICE_DOMAINS],
        self::REPORT_TYPE_CTV => [self::SLICE_DATE, self::SLICE_CHANNELS, self::SLICE_SOURCES, self::SLICE_ADVERTISERS, self::SLICE_CAMPAIGNS, self::SLICE_COUNTRIES, self::SLICE_APP_NAME, self::SLICE_APP_BUNDLE_ID],
    ];

    /**
     * @inheritdoc
     */
    public function runReportAndDownload()
    {
        $allElement = $this->filterElementByTagNameAndText('label', 'All');
        if (!$allElement) {
            $this->runReportAndDownload();
        }

        $downloadButton = $this->filterElementByTagNameAndText('span', "Download");
        if ($downloadButton) {
            $downloadButton->click();
            $this->logger->debug('Click download report');
            return;
        }

        throw new \Exception("Id of Download button is not correct. Need recheck code base");
    }
}