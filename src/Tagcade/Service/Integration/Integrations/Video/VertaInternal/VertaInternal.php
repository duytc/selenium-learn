<?php


namespace Tagcade\Service\Integration\Integrations\Video\VertaInternal;

use Tagcade\Service\Fetcher\Params\Verta\VertaInternalPartnerParam;
use Tagcade\Service\Fetcher\PartnerFetcherInterface;
use Tagcade\Service\Integration\Integrations\IntegrationInterface;
use Tagcade\Service\Integration\Integrations\IntegrationVideoDemandPartnerAbstract;
use Tagcade\Service\WebDriverServiceInterface;

class VertaInternal extends IntegrationVideoDemandPartnerAbstract implements IntegrationInterface
{
    const INTEGRATION_C_NAME = 'video-verta-internal';

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
        return new VertaInternalPartnerParam($config);
    }
}