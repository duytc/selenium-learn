<?php

namespace Tagcade\Service\Integration\Integrations\Video\StreamRailExternal;

use Tagcade\Service\Fetcher\Params\StreamRailExternal\StreamRailExternalPartnerParam;
use Tagcade\Service\Fetcher\PartnerFetcherInterface;
use Tagcade\Service\Integration\Integrations\IntegrationDemandPartnerAbstract;
use Tagcade\Service\Integration\Integrations\IntegrationInterface;
use Tagcade\Service\WebDriverServiceInterface;

class StreamRailExternal extends IntegrationDemandPartnerAbstract implements IntegrationInterface
{
    const INTEGRATION_C_NAME = 'video-streamrail-external';

    /**
     * Across33 constructor.
     *
     * @param WebDriverServiceInterface $webDriverService
     * @param PartnerFetcherInterface $fetcher
     */
    public function __construct(WebDriverServiceInterface $webDriverService, PartnerFetcherInterface $fetcher)
    {
        parent::__construct($webDriverService);
        $this->partnerFetcher = $fetcher;
    }

    /**
     * @inheritdoc
     *
     */
    public function createPartnerParams($config)
    {
        return new StreamRailExternalPartnerParam($config);
    }
}