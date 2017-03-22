<?php

namespace Tagcade\Service\Integration\Integrations\DemandPartner\Openx;

use Tagcade\Service\Fetcher\PartnerFetcherInterface;
use Tagcade\Service\Integration\Integrations\IntegrationDemandPartnerAbstract;
use Tagcade\Service\Integration\Integrations\IntegrationInterface;
use Tagcade\Service\WebDriverServiceInterface;

class Openx extends IntegrationDemandPartnerAbstract implements IntegrationInterface
{
    const INTEGRATION_C_NAME = 'demand-partner-openx';

    /**
     * Openx constructor.
     * @param WebDriverServiceInterface $webDriverService
     * @param PartnerFetcherInterface $fetcher
     */
    public function __construct(WebDriverServiceInterface $webDriverService, PartnerFetcherInterface $fetcher)
    {
        parent::__construct($webDriverService);
        $this->partnerFetcher = $fetcher;
    }
}