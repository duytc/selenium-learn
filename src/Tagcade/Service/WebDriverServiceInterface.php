<?php

namespace Tagcade\Service;

use Tagcade\Service\Fetcher\PartnerFetcherInterface;
use Tagcade\Service\Integration\ConfigInterface;

interface WebDriverServiceInterface
{
    /**
     * do get data
     *
     * @param PartnerFetcherInterface $partnerFetcher
     * @param ConfigInterface $config
     * @return bool|int
     * @throws \Exception
     */
    public function doGetData(PartnerFetcherInterface $partnerFetcher, ConfigInterface $config);
}