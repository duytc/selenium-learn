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
    }

    /**
     * @return String
     */
    public function getReportType()
    {
        return $this->reportType;
    }

    public function isDailyBreakdown()
    {
        return true;
    }
}