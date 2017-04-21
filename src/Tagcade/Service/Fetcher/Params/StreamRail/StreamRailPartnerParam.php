<?php

namespace Tagcade\Service\Fetcher\Params\StreamRail;


use Tagcade\Service\Fetcher\Params\PartnerParams;
use Tagcade\Service\Integration\ConfigInterface;

class StreamRailPartnerParam extends PartnerParams implements StreamRailPartnerParamInterface
{
    const PARAM_KEY_FIRST_DIMENSION = 'firstDimension';
    const PARAM_KEY_SECOND_DIMENSION = 'secondDimension';

    /**
     * @var string
     */
    protected $firstDimension;

    /**
     * @var string
     */
    protected $secondDimension;

    /**
     * SpringServePartnerParam constructor.
     * @param ConfigInterface $config
     */
    public function __construct(ConfigInterface $config)
    {
        parent::__construct($config);
        $this->firstDimension = $config->getParamValue(self::PARAM_KEY_FIRST_DIMENSION, null);
        $this->secondDimension = $config->getParamValue(self::PARAM_KEY_SECOND_DIMENSION, null);
    }

    /**
     * @return string
     */
    public function getFirstDimension()
    {
        return $this->firstDimension;
    }

    /**
     * @return string
     */
    public function getSecondDimension()
    {
        return $this->secondDimension;
    }
}