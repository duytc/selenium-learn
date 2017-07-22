<?php

namespace Tagcade\Service\Fetcher\Params\Cedato;


use Tagcade\Service\Fetcher\Params\PartnerParams;
use Tagcade\Service\Integration\ConfigInterface;

class CedatoPartnerParams extends PartnerParams implements CedatoPartnerParamInterface
{
    const PARAM_KEY_REPORT_TYPE = 'reportType';

    /**
     * @var string
     */
    protected $reportType;

    /**
     * CedatoPartnerParams constructor.
     * @param ConfigInterface $config
     */
    public function __construct(ConfigInterface $config)
    {
        parent::__construct($config);
        $this->reportType = $config->getParamValue(self::PARAM_KEY_REPORT_TYPE, null);

        /** Cedato do not have date column in the report, so we automatically set dailyBreakdown to true */
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