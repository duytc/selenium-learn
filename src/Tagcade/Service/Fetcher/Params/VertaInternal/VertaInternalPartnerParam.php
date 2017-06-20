<?php

namespace Tagcade\Service\Fetcher\Params\VertaInternal;


use Tagcade\Service\Fetcher\Params\PartnerParams;
use Tagcade\Service\Integration\ConfigInterface;

class VertaInternalPartnerParam extends PartnerParams implements VertaInternalPartnerParamInterface
{
    const PARAM_KEY_SLIDE = 'slice';
    const PARAM_KEY_REPORT_TYPE = 'reportType';

    /** @var  mixed */
    private $slice;

    /** @var  string */
    private $reportType;

    /**
     * VertaPartnerParam constructor.
     * @param ConfigInterface $config
     */
    public function __construct(ConfigInterface $config)
    {
        parent::__construct($config);
        $this->slice = $config->getParamValue(self::PARAM_KEY_SLIDE, []);
        $this->reportType = $config->getParamValue(self::PARAM_KEY_REPORT_TYPE, null);

        /** Like Cedato, Verta do not have date column in the report, so we automatically set dailyBreakdown to true */
        $this->setDailyBreakdown(true);
    }

    /**
     * @return string
     */
    public function getSlice()
    {
        return $this->slice;
    }

    /**
     * @return string
     */
    public function getReportType()
    {
        return $this->reportType;
    }
}