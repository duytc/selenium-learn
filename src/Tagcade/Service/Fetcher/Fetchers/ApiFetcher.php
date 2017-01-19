<?php

namespace Tagcade\Service\Fetcher\Fetchers;

use Tagcade\Service\Fetcher\ApiParameterInterface;
use Tagcade\Service\Fetcher\FetcherInterface;
use Tagcade\Service\Fetcher\Fetchers\Api\ApiFetcherInterface;

class ApiFetcher extends BaseFetcher implements FetcherInterface
{
	const TYPE = 'api';
	/**
	 * @var array
	 */
	private $apiFetchers;

	/**
	 * ApiFetcher constructor.
	 * @param array $apiFetchers
	 */
	public function __construct(array $apiFetchers)
	{
		$this->apiFetchers = [];
		/**@var ApiFetcherInterface $apiFetcher */
		foreach ($apiFetchers as $apiFetcher) {
			if (!$apiFetcher instanceof ApiFetcherInterface) {
				return;
			}
			$this->apiFetchers [] = $apiFetcher;
		}
	}

	/**
	 * @inheritdoc
	 */
	public function execute(ApiParameterInterface $parameters)
	{
		/**@var ApiFetcherInterface $apiFetcher */
		foreach ($this->apiFetchers as $apiFetcher) {
			if (!$apiFetcher->supportIntegration($parameters)) {
				continue;
			}
			$apiFetcher->doGetData($parameters);
		}
	}
}