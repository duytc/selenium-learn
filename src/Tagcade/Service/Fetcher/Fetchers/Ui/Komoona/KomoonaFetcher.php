<?php

namespace Tagcade\Service\Fetcher\Fetchers\Ui\Komoona;


use Tagcade\Service\Fetcher\Fetchers\Ui\AbstractUiFetcher;

class KomoonaFetcher extends AbstractUiFetcher implements KomoonaFetcherInterface
{
    /**
     * @inheritdoc
     */
    public function getIntegrationCName()
    {
        return 'across33';
    }
}