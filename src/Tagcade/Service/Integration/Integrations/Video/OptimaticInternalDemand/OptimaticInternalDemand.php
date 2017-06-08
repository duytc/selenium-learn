<?php

namespace Tagcade\Service\Integration\Integrations\Video\OptimaticInternalDemand;

use Tagcade\Service\Fetcher\Params\OptimaticInternalDemand\OptimaticInternalDemandPartnerParams;
use Tagcade\Service\Fetcher\PartnerFetcherInterface;
use Tagcade\Service\Integration\Integrations\IntegrationInterface;
use Tagcade\Service\Integration\Integrations\IntegrationVideoDemandPartnerAbstract;
use Tagcade\Service\WebDriverServiceInterface;

class OptimaticInternalDemand extends IntegrationVideoDemandPartnerAbstract implements IntegrationInterface
{
    const INTEGRATION_C_NAME = 'video-optimatic-internal-demand';

    /**
     * Media constructor.
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
     * override because of new param "reportType"
     */
    public function createPartnerParams($config)
    {
        return new OptimaticInternalDemandPartnerParams($config);
    }
}