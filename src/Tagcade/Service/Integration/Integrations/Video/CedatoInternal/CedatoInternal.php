<?php

namespace Tagcade\Service\Integration\Integrations\Video\CedatoInternal;

use Tagcade\Service\Fetcher\Params\Cedato\CedatoPartnerParams;
use Tagcade\Service\Fetcher\PartnerFetcherInterface;
use Tagcade\Service\Integration\Integrations\IntegrationInterface;
use Tagcade\Service\Integration\Integrations\IntegrationVideoDemandPartnerAbstract;
use Tagcade\Service\WebDriverServiceInterface;

class CedatoInternal extends IntegrationVideoDemandPartnerAbstract implements IntegrationInterface
{
    /*
     * Command to create:
     * php app/console ur:integration:create video-cedato-internal "Cedato-Internal" -p "username,password:secure,dateRange:dynamicDateRange" -a -vv
     */
    const INTEGRATION_C_NAME = 'video-cedato-internal';

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
     * override because set dailyBreakdown default is true
     */
    public function createPartnerParams($config)
    {
        return new CedatoPartnerParams($config);
    }
}