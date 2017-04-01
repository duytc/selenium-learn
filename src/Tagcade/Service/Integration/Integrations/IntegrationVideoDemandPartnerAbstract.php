<?php

namespace Tagcade\Service\Integration\Integrations;

use Tagcade\Service\Integration\ConfigInterface;
use Tagcade\Service\Fetcher\PartnerFetcherInterface;
use Tagcade\Service\WebDriverServiceInterface;

abstract class IntegrationVideoDemandPartnerAbstract extends IntegrationAbstract implements IntegrationInterface
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
        return $this->webDriver->doGetData($this->partnerFetcher, $config);
    }
}