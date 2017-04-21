<?php

namespace Tagcade\Service\Integration\Integrations\Video\StreamRail;

use Tagcade\Service\Fetcher\Params\StreamRail\StreamRailPartnerParam;
use Tagcade\Service\Fetcher\PartnerFetcherInterface;
use Tagcade\Service\Integration\Integrations\IntegrationDemandPartnerAbstract;
use Tagcade\Service\Integration\Integrations\IntegrationInterface;
use Tagcade\Service\WebDriverServiceInterface;

class StreamRail extends IntegrationDemandPartnerAbstract implements IntegrationInterface
{
    const INTEGRATION_C_NAME = 'video-streamrail';

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
        return new StreamRailPartnerParam($config);
    }
}