<?php

namespace Tagcade\Service\Fetcher\Params\Verta;

use Tagcade\Service\Fetcher\Params\PartnerParams;
use Tagcade\Service\Integration\ConfigInterface;

class VertaPartnerParam extends PartnerParams implements VertaPartnerParamInterface
{
    const PARAM_KEY_REPORT = 'report';
    const PARAM_KEY_CROSS_REPORTS = 'crossReports';

    /** @var array */
    private $crossReports;

    /** @var string */
    private $report;

    /**
     * VertaPartnerParam constructor.
     * @param ConfigInterface $config
     */
    public function __construct(ConfigInterface $config)
    {
        parent::__construct($config);

        $this->report = $config->getParamValue(self::PARAM_KEY_REPORT, '');
        $this->crossReports = $config->getParamValue(self::PARAM_KEY_CROSS_REPORTS, []);

        /** Like Cedato, Verta do not have date column in the report, so we automatically set dailyBreakdown to true */
        $this->setDailyBreakdown(true);
    }

    /**
     * @inheritdoc
     */
    public function getCrossReports()
    {
        return $this->crossReports;
    }

    /**
     * @inheritdoc
     */
    public function setCrossReports(array $crossReports)
    {
        $this->crossReports = $crossReports;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getReport()
    {
        return $this->report;
    }

    /**
     * @inheritdoc
     */
    public function setReport($report)
    {
        $this->report = $report;

        return $this;
    }
}