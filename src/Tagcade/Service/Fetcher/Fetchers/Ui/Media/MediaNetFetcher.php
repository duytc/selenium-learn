<?php

namespace Tagcade\Service\Fetcher\Fetchers\Ui\Media;

use Tagcade\Service\Fetcher\Fetchers\Ui\AbstractUiFetcher;

class MediaNetFetcher extends AbstractUiFetcher implements MediaNetFetcherInterface
{
    /**
     * @inheritdoc
     */
    public function getIntegrationCName()
    {
        return 'across33';
    }
}