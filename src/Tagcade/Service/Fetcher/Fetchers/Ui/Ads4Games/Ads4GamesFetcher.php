<?php


namespace Tagcade\Service\Fetcher\Fetchers\Ui\Ads4Games;


use Tagcade\Service\Fetcher\Fetchers\Ui\AbstractUiFetcher;

class Ads4GamesFetcher extends AbstractUiFetcher implements Ads4GamesFetcherInterface
{
    /**
     * @inheritdoc
     */
    public function getIntegrationCName()
    {
        return 'across33';
    }
}