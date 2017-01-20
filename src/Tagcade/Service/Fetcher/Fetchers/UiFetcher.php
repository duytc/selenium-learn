<?php

namespace Tagcade\Service\Fetcher\Fetchers;

use Psr\Log\LoggerInterface;
use Tagcade\DataSource\PartnerFetcherInterface;
use Tagcade\Service\Fetcher\ApiParameterInterface;
use Tagcade\Service\Fetcher\FetcherInterface;
use Tagcade\Service\Fetcher\Fetchers\Ui\UiFetcherInterface;

class UiFetcher extends BaseFetcher implements FetcherInterface
{
    const TYPE = FetcherInterface::TYPE_UI;

    /** @var array|UiFetcherInterface[] */
    protected $uiFetchers;

    /** @var LoggerInterface */
    protected $logger;

    /**
     * UiFetcher constructor.
     * @param array $uiFetchers
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger, array $uiFetchers)
    {
        $this->logger = $logger;

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
        /**@var UiFetcherInterface|PartnerFetcherInterface $uiFetcher */
        foreach ($this->uiFetchers as $uiFetcher) {
            if (!$uiFetcher->supportIntegration($parameters)) {
                continue;
            }

            $uiFetcher->doGetData($parameters);
        }
    }
}