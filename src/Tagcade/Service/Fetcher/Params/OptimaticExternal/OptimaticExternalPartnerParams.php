<?php

namespace Tagcade\Service\Fetcher\Params\OptimaticExternal;


use Tagcade\Service\Fetcher\Params\PartnerParams;
use Tagcade\Service\Integration\ConfigInterface;

class OptimaticExternalPartnerParams extends PartnerParams implements OptimaticExternalPartnerParamsInterface
{
    const PARAM_KEY_REPORT_TYPE = 'reportType';
    const PARAM_KEY_PLACEMENTS = 'placements';

    /**
     * @var string
     */
    protected $reportType;

    /**
     * @var string
     */
    protected $placements;

    /**
     * OptimaticExternalPartnerParams constructor.
     * @param ConfigInterface $config
     */
    public function __construct(ConfigInterface $config)
    {
        parent::__construct($config);
        $this->reportType = $config->getParamValue(self::PARAM_KEY_REPORT_TYPE, null);
        $this->placements = $config->getParamValue(self::PARAM_KEY_PLACEMENTS, null);

        $this->setDailyBreakdown(true);
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
    public function getPlacements()
    {
        return $this->placements;
    }

}