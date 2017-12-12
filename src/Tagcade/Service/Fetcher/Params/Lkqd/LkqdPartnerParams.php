<?php

namespace Tagcade\Service\Fetcher\Params\Lkqd;


use Tagcade\Service\Fetcher\Params\PartnerParams;
use Tagcade\Service\Integration\ConfigInterface;

class LkqdPartnerParams extends PartnerParams implements LkqdPartnerParamInterface
{
    const PARAM_KEY_TIMEZONE = 'timezone';
    const PARAM_KEY_DIMENSIONS = 'dimensions';
    const PARAM_KEY_METRICS = 'metrics';
    const DEFAULT_TIME_ZONE = 'UTC';

    /**
     * @var string
     */
    protected $timezone;

    protected $dimensions;

    protected $metrics;

    /**
     * CedatoPartnerParams constructor.
     * @param ConfigInterface $config
     */
    public function __construct(ConfigInterface $config)
    {
        parent::__construct($config);
        $this->timezone = $config->getParamValue(self::PARAM_KEY_TIMEZONE, self::DEFAULT_TIME_ZONE);
        $this->dimensions = $config->getParamValue(self::PARAM_KEY_DIMENSIONS, []);
        $this->metrics = $config->getParamValue(self::PARAM_KEY_METRICS, []);
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

    /**
     * @return mixed
     */
    public function getMetrics()
    {
        return $this->metrics;
    }
}