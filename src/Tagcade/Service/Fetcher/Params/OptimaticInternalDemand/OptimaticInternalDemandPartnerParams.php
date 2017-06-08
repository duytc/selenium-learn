<?php

namespace Tagcade\Service\Fetcher\Params\OptimaticInternalDemand;


use Tagcade\Service\Fetcher\Params\PartnerParams;
use Tagcade\Service\Integration\ConfigInterface;

class OptimaticInternalDemandPartnerParams extends PartnerParams implements OptimaticInternalDemandPartnerParamsInterface
{
    const PARAM_KEY_REPORT_TYPE = 'reportType';
    const PARAM_KEY_ADVERTISER = 'advertiser';
    const PARAM_KEY_PLACEMENTS = 'placements';
    const PARAM_KEY_ALL_TREND_BY_ADV = 'allTrendByAdv';

    /**
     * @var string
     */
    protected $reportType;

    /**
     * for reportType = Ad Source Report and Domain By Ad Source
     * @var string
     */
    protected $advertiser;

    /**
     * for reportType = Domain By Ad Source
     * @var string
     */
    protected $placements;

    /**
     * for reportType = Trend By Advertiser
     * @var string
     */
    protected $allTrendByAdv;

    /**
     * OptimaticInternalDemandPartnerParams constructor.
     * @param ConfigInterface $config
     */
    public function __construct(ConfigInterface $config)
    {
        parent::__construct($config);
        $this->reportType = $config->getParamValue(self::PARAM_KEY_REPORT_TYPE, null);
        $this->advertiser = $config->getParamValue(self::PARAM_KEY_ADVERTISER, null);
        $this->placements = $config->getParamValue(self::PARAM_KEY_PLACEMENTS, null);
        $this->allTrendByAdv = $config->getParamValue(self::PARAM_KEY_ALL_TREND_BY_ADV, null);
    }

    /**
     * @return String
     */
    public function getReportType()
    {
        return $this->reportType;
    }

    /**
     * @return String
     */
    public function getAdvertiser()
    {
        return $this->advertiser;
    }

    /**
     * @return String
     */
    public function getPlacements()
    {
        return $this->placements;
    }

    /**
     * @return String
     */
    public function getAllTrendByAdv()
    {
        return $this->allTrendByAdv;
    }
}