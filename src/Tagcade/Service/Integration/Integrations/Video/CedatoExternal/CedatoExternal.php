<?php

namespace Tagcade\Service\Integration\Integrations\Video\CedatoExternal;

use Tagcade\Service\Fetcher\Params\Cedato\CedatoPartnerParams;
use Tagcade\Service\Fetcher\PartnerFetcherInterface;
use Tagcade\Service\Integration\Integrations\IntegrationInterface;
use Tagcade\Service\Integration\Integrations\IntegrationVideoDemandPartnerAbstract;
use Tagcade\Service\WebDriverServiceInterface;

class CedatoExternal extends IntegrationVideoDemandPartnerAbstract implements IntegrationInterface
{
    /*
     * create command
     * php app/console ur:integration:create video-cedato-external "Cedato-External" -p "username,password:secure,dateRange:dynamicDateRange,reportType:option:Supply;Supply by Demand Sources;Demand Sources by Supply;Demand" -a -vv
     *
     * update command
     * php app/console ur:integration:update video-cedato-external "Cedato-External" -p "username,password:secure,dateRange:dynamicDateRange,reportType:option:Supply;Supply by Demand Sources;Demand Sources by Supply;Demand" -a -vv
     */
    const INTEGRATION_C_NAME = 'video-cedato-external';

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
     * and override because set dailyBreakdown default is true
     */
    public function createPartnerParams($config)
    {
        return new CedatoPartnerParams($config);
    }
}