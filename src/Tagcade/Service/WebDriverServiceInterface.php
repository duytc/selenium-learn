<?php

namespace Tagcade\Service;

use Exception;
use Tagcade\Service\Fetcher\PartnerFetcherInterface;
use Tagcade\Service\Fetcher\Params\PartnerParamInterface;

interface WebDriverServiceInterface
{
    /**
     * do get data
     *
     * @param PartnerFetcherInterface $partnerFetcher
     * @param PartnerParamInterface $partnerParam
     * @return bool|int
     * @throws Exception
     */
    public function doGetData(PartnerFetcherInterface $partnerFetcher, PartnerParamInterface $partnerParam);
}