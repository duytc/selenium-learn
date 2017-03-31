<?php

namespace Tagcade\Service\Fetcher;


use Tagcade\Service\Integration\ConfigInterface;

class CedatoPartnerParams extends PartnerParams implements CedatoPartnerParamInterface
{
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

        $reportType = $config->getParams()['reportType'];

        $this->reportType = $reportType;
    }

    /**
     * @return String
     */
    public function getReportType()
    {
        return $this->reportType;
    }
}