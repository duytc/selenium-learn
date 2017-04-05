<?php

namespace Tagcade\Service\Fetcher\Params\SpringServe;


use Tagcade\Service\Fetcher\Params\PartnerParams;
use Tagcade\Service\Integration\ConfigInterface;

class SpringServePartnerParam extends PartnerParams implements SpringServePartnerParamInterface
{
    const PARAM_KEY_ACCOUNT = 'account';

    /**
     * @var string
     */
    protected $account;

    /**
     * SpringServePartnerParam constructor.
     * @param ConfigInterface $config
     */
    public function __construct(ConfigInterface $config)
    {
        parent::__construct($config);
        $this->account = $config->getParamValue(self::PARAM_KEY_ACCOUNT, null);
    }

    /**
     * @return string
     */
    public function getAccount()
    {
        return $this->account;
    }
}