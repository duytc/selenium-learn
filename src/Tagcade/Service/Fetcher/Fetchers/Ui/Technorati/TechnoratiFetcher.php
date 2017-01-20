<?php

namespace Tagcade\Service\Fetcher\Fetchers\Ui\Technorati;

use Tagcade\Service\Fetcher\Fetchers\Ui\AbstractUiFetcher;

class TechnoratiFetcher extends AbstractUiFetcher implements TechnoratiFetcherInterface
{
    /**
     * @inheritdoc
     */
    public function getIntegrationCName()
    {
        return 'across33';
    }
}