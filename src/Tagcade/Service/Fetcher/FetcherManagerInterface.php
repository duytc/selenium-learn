<?php


namespace Tagcade\Service\Fetcher;


interface FetcherManagerInterface
{
    /**
     * @param $type
     * @return FetcherInterface
     */
    public function getFetcher($type);
}