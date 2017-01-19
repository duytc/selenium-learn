<?php

namespace Tagcade\Service\Fetcher\Fetchers\Api;

use Tagcade\Service\Fetcher\ApiParameterInterface;
use Tagcade\Service\Fetcher\Fetchers\ApiFetcher;

class OpenxApiFetcher implements ApiFetcherInterface
{
	const INTEGRATION_C_NAME = 'open-x';

	function supportIntegration(ApiParameterInterface $parameter)
	{
		$allParams = $parameter->getParams();
		$type = $allParams['type'];
		$integrationCName = $parameter->getIntegrationCName();

		return (($integrationCName == self::INTEGRATION_C_NAME) && ($type == ApiFetcher::TYPE_API));
	}

	function doGetData(ApiParameterInterface $parameter)
	{
		// TODO: Implement doGetData() method.
	}
}