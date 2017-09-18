<?php

namespace Tagcade\Service\Fetcher\Params\OptimaticInternalSupply;


use Tagcade\Service\Fetcher\Params\PartnerParams;
use Tagcade\Service\Integration\ConfigInterface;

class OptimaticInternalSupplyPartnerParams extends PartnerParams implements OptimaticInternalSupplyPartnerParamsInterface
{
    const PARAM_KEY_REPORT_TYPE = 'reportType';

    /**
     * @var string
     */
    protected $reportType;

    /**
     * OptimaticInternalSupplyPartnerParams constructor.
     * @param ConfigInterface $config
     */
    public function __construct(ConfigInterface $config)
    {
        parent::__construct($config);
        $this->reportType = $config->getParamValue(self::PARAM_KEY_REPORT_TYPE, null);

        $this->setDailyBreakdown(true);
    }

    /**
     * @return String
     */
    public function getReportType()
    {
        return $this->reportType;
    }

}