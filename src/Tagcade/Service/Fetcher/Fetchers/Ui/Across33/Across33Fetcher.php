<?php

namespace Tagcade\Service\Fetcher\Fetchers\Ui\Across33;

use Tagcade\Service\Fetcher\Fetchers\Ui\AbstractUiFetcher;

class Across33Fetcher extends AbstractUiFetcher implements Across33FetcherInterface
{
    /**
     * @inheritdoc
     */
    public function getIntegrationCName()
    {
        return 'across33';
    }
}