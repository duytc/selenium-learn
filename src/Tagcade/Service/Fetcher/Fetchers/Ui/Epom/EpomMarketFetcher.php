<?php


namespace Tagcade\Service\Fetcher\Fetchers\Ui\Epom;


use Tagcade\Service\Fetcher\Fetchers\Ui\AbstractUiFetcher;

class EpomMarketFetcher extends AbstractUiFetcher implements EpomMarketFetcherInterface
{
    /**
     * @inheritdoc
     */
    public function getIntegrationCName()
    {
        return 'across33';
    }
}