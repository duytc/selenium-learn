<?php
namespace Tagcade\Service\Integration\Integrations\Video\LkqdIntegration;

use Tagcade\Service\Fetcher\PartnerFetcherInterface;
use Tagcade\Service\Integration\Integrations\IntegrationDemandPartnerAbstract;
use Tagcade\Service\Integration\Integrations\IntegrationInterface;
use Tagcade\Service\WebDriverServiceInterface;

class LkqdIntegration extends IntegrationDemandPartnerAbstract implements IntegrationInterface
{
    const INTEGRATION_C_NAME = 'video-lkqd';

    public function __construct(WebDriverServiceInterface $webDriverService, PartnerFetcherInterface $fetcher)
    {
        parent::__construct($webDriverService);
        $this->partnerFetcher = $fetcher;
    }
}