<?php

namespace Tagcade\Service\Fetcher\Fetchers;

use Tagcade\Service\Fetcher\ApiParameterInterface;
use Tagcade\Service\Fetcher\FetcherInterface;

class ApiFetcher extends BaseFetcher implements FetcherInterface
{
    const TYPE = 'api';

    public function execute(ApiParameterInterface $parameters)
    {
        // TODO: Implement execute() method.
    }
}