<?php

namespace Tagcade\Service\Fetcher\Params\SpringServe;


use Tagcade\Service\Fetcher\Params\PartnerParams;
use Tagcade\Service\Integration\ConfigInterface;

class SpringServePartnerParam extends PartnerParams implements SpringServePartnerParamInterface
{
    const PARAM_KEY_ACCOUNT = 'account';
    const PARAM_KEY_TIME_ZONE = 'timezone';
    const PARAM_KEY_INTERVAL = 'interval';
    const PARAM_KEY_DIMENSIONS = 'dimensions';

    /**
     * @var string
     */
    protected $account;

    /**
     * @var string
     */
    protected $timeZone;

    /**
     * @var string
     */
    protected $interval;

    /**
     * @var array
     */
    protected $dimensions;


    /**
     * SpringServePartnerParam constructor.
     * @param ConfigInterface $config
     */
    public function __construct(ConfigInterface $config)
    {
        parent::__construct($config);
        $this->account = $config->getParamValue(self::PARAM_KEY_ACCOUNT, null);
        $this->timeZone = $config->getParamValue(self::PARAM_KEY_TIME_ZONE, "UTC");
        $this->interval = 'Day';
        $this->dimensions = $config->getParamValue(self::PARAM_KEY_DIMENSIONS, []);
    }

    /**
     * @return string
     */
    public function getAccount()
    {
        return $this->account;
    }

    /**
     * @return string
     */
    public function getTimeZone()
    {
        return $this->timeZone;
    }

    /**
     * @return string
     */
    public function getInterval()
    {
        return $this->interval;
    }

    /**
     * @return array
     */
    public function getDimensions()
    {
        return $this->dimensions;
    }
}