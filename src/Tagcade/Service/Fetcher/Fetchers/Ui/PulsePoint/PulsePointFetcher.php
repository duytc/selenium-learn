<?php

namespace Tagcade\Service\Fetcher\Fetchers\Ui\PulsePoint;


use Tagcade\Service\Fetcher\Fetchers\Ui\AbstractUiFetcher;

class PulsePointFetcher extends AbstractUiFetcher implements PulsePointFetcherInterface
{
    /**
     * @inheritdoc
     */
    public function getIntegrationCName()
    {
        return 'across33';
    }
}