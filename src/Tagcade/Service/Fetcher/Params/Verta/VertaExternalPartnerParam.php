<?php

namespace Tagcade\Service\Fetcher\Params\Verta;

use Tagcade\Service\Integration\ConfigInterface;

class VertaExternalPartnerParam extends VertaPartnerParam
{
    /**
     * VertaPartnerParam constructor.
     * @param ConfigInterface $config
     */
    public function __construct(ConfigInterface $config)
    {
        parent::__construct($config);

        $this->setCrossReports($config->getParamValue(self::PARAM_KEY_CROSS_REPORTS, []));
    }
}