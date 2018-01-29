<?php

namespace Tagcade\Service\Fetcher\Params\StreamRailExternal;


use Tagcade\Service\Fetcher\Params\PartnerParams;
use Tagcade\Service\Integration\ConfigInterface;

class StreamRailExternalPartnerParam extends PartnerParams implements StreamRailExternalPartnerParamInterface
{
    /**
     * StreamRailExternalPartnerParam constructor.
     * @param ConfigInterface $config
     * @throws \Exception
     */
    public function __construct(ConfigInterface $config)
    {
        parent::__construct($config);

        $this->setDailyBreakdown(true);
    }
}