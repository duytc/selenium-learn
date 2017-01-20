<?php

namespace Tagcade\Service\Fetcher\Fetchers\Ui\DefyMedia;

use Tagcade\Service\Fetcher\Fetchers\Ui\AbstractUiFetcher;

class DefyMediaFetcher extends AbstractUiFetcher implements DefyMediaFetcherInterface
{
    /**
     * @inheritdoc
     */
    public function getIntegrationCName()
    {
        return 'across33';
    }
}