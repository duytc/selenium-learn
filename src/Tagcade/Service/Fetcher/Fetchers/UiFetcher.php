<?php

namespace Tagcade\Service\Fetcher\Fetchers;

use Tagcade\Service\Fetcher\ApiParameterInterface;
use Tagcade\Service\Fetcher\FetcherInterface;
use Tagcade\Service\Fetcher\Fetchers\Ui\UiFetcherInterface;

class UiFetcher extends BaseFetcher implements FetcherInterface
{
    const TYPE = FetcherInterface::TYPE_UI;

    protected $uiFetchers;

    /**
     * UiFetcher constructor.
     * @param array $uiFetchers
     */
    public function __construct(array $uiFetchers)
    {
        $this->uiFetchers = [];

        /**@var UiFetcherInterface $uiFetcher */
        foreach ($uiFetchers as $uiFetcher) {
            if (!$uiFetcher instanceof UiFetcherInterface) {
                return;
            }
            $this->uiFetchers [] = $uiFetcher;
        }
    }

    /**
     * @inheritdoc
     */
    public function execute(ApiParameterInterface $parameters)
    {
        /**@var UiFetcherInterface $uiFetcher */
        foreach ($this->uiFetchers as $uiFetcher) {
            if (!$uiFetcher->supportIntegration($parameters)) {
                continue;
            }

            $uiFetcher->doGetData($parameters);
        }
    }
}