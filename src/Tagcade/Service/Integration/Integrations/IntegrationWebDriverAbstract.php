<?php

namespace Tagcade\Service\Integration\Integrations;

use Tagcade\Service\Fetcher\PartnerParams;
use Tagcade\Service\Integration\ConfigInterface;
use Tagcade\Service\Fetcher\PartnerFetcherInterface;
use Tagcade\Service\WebDriverServiceInterface;

abstract class IntegrationWebDriverAbstract extends IntegrationAbstract implements IntegrationWebDriverInterface
{
    /** @var WebDriverServiceInterface */
    protected $webDriver;

    /** @var PartnerFetcherInterface */
    protected $partnerFetcher;

    public function __construct(WebDriverServiceInterface $webDriver)
    {
        $this->webDriver = $webDriver;
    }

    /**
     * @inheritdoc
     */
    public function run(ConfigInterface $config)
    {
        $partnerParams = $this->createPartnerParams($config);

        return $this->webDriver->doGetData($this->partnerFetcher, $partnerParams);
    }

    /**
     * @inheritdoc
     */
    public function createPartnerParams($config)
    {
        return new PartnerParams($config);
    }
}