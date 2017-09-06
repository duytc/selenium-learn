<?php

namespace Tagcade\Service\Fetcher\Params\StreamRail;


use Tagcade\Service\Fetcher\Params\PartnerParams;
use Tagcade\Service\Integration\ConfigInterface;

class StreamRailPartnerParam extends PartnerParams implements StreamRailPartnerParamInterface
{
    const PARAM_KEY_PRIMARY_DIMENSION = 'primaryDimension';
    const PARAM_KEY_SECONDARY_DIMENSION = 'secondaryDimension';

    /**
     * @var string
     */
    protected $primaryDimension;

    /**
     * @var string
     */
    protected $secondaryDimension;

    /**
     * SpringServePartnerParam constructor.
     * @param ConfigInterface $config
     */
    public function __construct(ConfigInterface $config)
    {
        parent::__construct($config);
        $this->primaryDimension = $config->getParamValue(self::PARAM_KEY_PRIMARY_DIMENSION, null);
        $this->secondaryDimension = $config->getParamValue(self::PARAM_KEY_SECONDARY_DIMENSION, null);

        $this->setDailyBreakdown(true);
    }

    /**
     * @return string
     */
    public function getPrimaryDimension()
    {
        return $this->primaryDimension;
    }

    /**
     * @return string
     */
    public function getSecondaryDimension()
    {
        return $this->secondaryDimension;
    }
}