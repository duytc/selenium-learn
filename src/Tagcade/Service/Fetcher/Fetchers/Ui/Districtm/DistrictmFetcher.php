<?php

namespace Tagcade\Service\Fetcher\Fetchers\Ui\Districtm;

use Tagcade\Service\Fetcher\Fetchers\Ui\AbstractUiFetcher;

class DistrictmFetcher extends AbstractUiFetcher implements DistrictmFetcherInterface
{
    /**
     * @inheritdoc
     */
    public function getIntegrationCName()
    {
        return 'across33';
    }
}