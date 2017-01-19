<?php

namespace Tagcade\Service\Fetcher\Fetchers\Ui;

use Tagcade\Service\Fetcher\ApiParameterInterface;

interface UiFetcherInterface
{
	const TYPE = 'api';

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