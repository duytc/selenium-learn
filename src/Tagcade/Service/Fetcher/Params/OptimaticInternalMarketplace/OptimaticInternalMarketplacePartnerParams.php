<?php

namespace Tagcade\Service\Fetcher\Params\OptimaticInternalMarketplace;


use Tagcade\Service\Fetcher\Params\PartnerParams;
use Tagcade\Service\Integration\ConfigInterface;

class OptimaticInternalMarketplacePartnerParams extends PartnerParams implements OptimaticInternalMarketplacePartnerParamsInterface
{
    const PARAM_KEY_REPORT_TYPE = 'reportType';
    const PARAM_KEY_PARTNERS = 'partners';
    const PARAM_KEY_PLACEMENTS = 'placements';

    /**
     * @var string
     */
    protected $reportType;

    /**
     * for reportType = Pay My Partners
     * @var string
     */
    protected $partners;

    /**
     * for reportType = Trend by Placement
     * @var string
     */
    protected $placements;

    /**
     * CedatoPartnerParams constructor.
     * @param ConfigInterface $config
     */
    public function __construct(ConfigInterface $config)
    {
        parent::__construct($config);
        $this->reportType = $config->getParamValue(self::PARAM_KEY_REPORT_TYPE, null);
        $this->partners = $config->getParamValue(self::PARAM_KEY_PARTNERS, null);
        $this->placements = $config->getParamValue(self::PARAM_KEY_PLACEMENTS, null);
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
    public function getPartners()
    {
        return $this->partners;
    }

    /**
     * @return String
     */
    public function getPlacements()
    {
        return $this->placements;
    }

    public function isDailyBreakdown()
    {
        return true;
    }
}