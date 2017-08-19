<?php

namespace Tagcade\Service\Fetcher\Params\Verta;

use Tagcade\Service\Fetcher\Params\PartnerParams;
use Tagcade\Service\Integration\ConfigInterface;

class VertaPartnerParam extends PartnerParams implements VertaPartnerParamInterface
{
    const PARAM_KEY_REPORT = 'report';
    const PARAM_KEY_CROSS_REPORT = 'crossReport';


    private $crossReport;
    private $report;

    /**
     * VertaPartnerParam constructor.
     * @param ConfigInterface $config
     */
    public function __construct(ConfigInterface $config)
    {
        parent::__construct($config);
        $this->report = $config->getParamValue(self::PARAM_KEY_REPORT, null);
        $this->crossReport = $config->getParamValue(self::PARAM_KEY_CROSS_REPORT, null);

        /** Like Cedato, Verta do not have date column in the report, so we automatically set dailyBreakdown to true */
        $this->setDailyBreakdown(true);
    }

    /**
     * @return string
     */
    public function getCrossReport()
    {
        return $this->crossReport;
    }

    /**
     * @return string
     */
    public function getReport()
    {
        return $this->report;
    }
}