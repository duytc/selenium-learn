<?php


namespace Tagcade\Service\Fetcher;


class FetcherManager implements FetcherManagerInterface
{
    /**
     * @var array
     */
    protected $fetchers;

    /**
     * FetcherManager constructor.
     * @param array $fetchers
     */
    public function __construct(array $fetchers)
    {
        $this->fetchers = [];

        foreach($fetchers as $fetcher) {
            if (!$fetcher instanceof FetcherInterface) {
                continue;
            }

            $this->fetchers[] = $fetcher;
        }
    }


    public function getFetcher($type)
    {
        /**
         * @var FetcherInterface $fetcher
         */
        foreach($this->fetchers as $fetcher) {
            if ($fetcher->supportType($type)) {
                return $fetcher;
            }
        }

        throw new \InvalidArgumentException(sprintf('Not found any fetcher support type "%s"', $type));
    }
}