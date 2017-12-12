<?php


namespace Tagcade\Service\Integration\Integrations\Video\LkqdDemandDeals;

use Tagcade\Service\Fetcher\Params\Lkqd\LkqdPartnerParams;
use Tagcade\Service\Fetcher\PartnerFetcherInterface;
use Tagcade\Service\Integration\Integrations\IntegrationInterface;
use Tagcade\Service\Integration\Integrations\IntegrationVideoDemandPartnerAbstract;
use Tagcade\Service\WebDriverServiceInterface;

class LkqdDemandDealsIntegration extends IntegrationVideoDemandPartnerAbstract implements IntegrationInterface
{
    /*
 * Command to create:
 * php app/console ur:integration:create video-lkqd-demand-deals "LKQD Demand Deals" -a -p "username,password:secure,dateRange:dynamicDateRange,timezone:option:UTC;Eastern,dimensions:multiOptions:Demand Deal;Demand Tag;Demand Order,metrics:multiOptions:Tag Requests;Impressions;Revenue;25;50;75;100"
 */
    const INTEGRATION_C_NAME = 'video-lkqd-demand-deals';

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
        return new LkqdPartnerParams($config);
    }
}