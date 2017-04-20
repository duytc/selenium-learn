<?php

namespace Tagcade\Service\Fetcher\Params\Lkqd;


use Tagcade\Service\Fetcher\Params\PartnerParams;
use Tagcade\Service\Integration\ConfigInterface;

class LkqdPartnerParams extends PartnerParams implements LkqdPartnerParamInterface
{
    const PARAM_KEY_REPORT_TYPE = 'timezone';
    const PARAM_KEY_DIMENSIONS = 'dimensions';
    const DEFAULT_TIME_ZONE = 'UTC';

    /**
     * @var string
     */
    protected $timezone;

    protected $dimensions;

    /**
     * CedatoPartnerParams constructor.
     * @param ConfigInterface $config
     */
    public function __construct(ConfigInterface $config)
    {
        parent::__construct($config);
        $this->timezone = $config->getParamValue(self::PARAM_KEY_REPORT_TYPE, self::DEFAULT_TIME_ZONE);
        $this->dimensions = $config->getParamValue(self::PARAM_KEY_DIMENSIONS, []);
    }

    /**
     * @return String
     */
    public function getTimeZone()
    {
        return $this->timezone;
    }

    /**
     * @return mixed
     */
    public function getDimensions()
    {
        return $this->dimensions;
    }
}