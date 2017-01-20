<?php


namespace Tagcade\Service\Fetcher\Fetchers\Ui\CpmBase;


use Tagcade\Service\Fetcher\Fetchers\Ui\AbstractUiFetcher;

class CpmBaseFetcher extends AbstractUiFetcher implements CpmBaseFetcherInterface
{
    /**
     * @inheritdoc
     */
    public function getIntegrationCName()
    {
        return 'across33';
    }
}