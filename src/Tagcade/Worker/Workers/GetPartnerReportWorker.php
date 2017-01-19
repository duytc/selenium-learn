<?php

namespace Tagcade\Worker\Workers;


// responsible for doing the background tasks assigned by the manager
// all public methods on the class represent tasks that can be done

use stdClass;
use Tagcade\Service\Fetcher\ApiParameter;
use Tagcade\Service\Fetcher\FetcherManagerInterface;

class GetPartnerReportWorker
{
    /**
     * @var FetcherManagerInterface
     */
    protected $fetcherManager;

    /**
     * GetPartnerReportWorker constructor.
     * @param FetcherManagerInterface $fetcherManager
     */
    public function __construct(FetcherManagerInterface $fetcherManager)
    {
        $this->fetcherManager = $fetcherManager;
    }


    public function getPartnerReport(StdClass $params)
    {
        $type = $params->type;
        $fetcher = $this->fetcherManager->getFetcher($type);

        $parameter = new ApiParameter($params->publisherId, $params->cname, json_decode($params->param, true));

        $fetcher->execute($parameter);
    }
}