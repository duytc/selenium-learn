<?php

namespace Tagcade\Service\Fetcher\Fetchers\Ui\YellowHammer;


use Tagcade\Service\Fetcher\Fetchers\Ui\AbstractUiFetcher;

class YellowHammerFetcher extends AbstractUiFetcher implements YellowHammerFetcherInterface
{
    /**
     * @inheritdoc
     */
    public function getIntegrationCName()
    {
        return 'across33';
    }
}