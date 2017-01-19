<?php

namespace Tagcade\Service\Fetcher\Fetchers\Api;
use Tagcade\Service\Fetcher\ApiParameterInterface;

interface ApiFetcherInterface
{
	const TYPE = 'ui';

	function supportIntegration(ApiParameterInterface $parameter);
	function doGetData(ApiParameterInterface $parameter);
}