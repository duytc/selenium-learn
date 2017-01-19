<?php

namespace Tagcade\Service\Fetcher\Fetchers\Api;
use Tagcade\Service\Fetcher\ApiParameterInterface;

interface ApiFetcherInterface
{
	const TYPE = 'ui';

	/**
	 * Check this fetcher support this integration or not
	 * @param ApiParameterInterface $parameter
	 * @return mixed
	 */
	function supportIntegration(ApiParameterInterface $parameter);

	/**
	 * Get data file
	 * @param ApiParameterInterface $parameter
	 * @return mixed
	 */
	function doGetData(ApiParameterInterface $parameter);
}