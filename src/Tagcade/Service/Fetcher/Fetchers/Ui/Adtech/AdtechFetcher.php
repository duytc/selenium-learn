<?php


namespace Tagcade\Service\Fetcher\Fetchers\Ui\Adtech;


use Tagcade\Service\Fetcher\Fetchers\Ui\AbstractUiFetcher;

class AdtechFetcher extends AbstractUiFetcher implements AdtechFetcherInterface
{
    /**
     * @inheritdoc
     */
    public function getIntegrationCName()
    {
        return 'across33';
    }
}