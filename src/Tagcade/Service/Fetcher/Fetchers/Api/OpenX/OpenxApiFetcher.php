<?php

namespace Tagcade\Service\Fetcher\Fetchers\Api\OpenX;

use Tagcade\Service\Fetcher\ApiParameterInterface;
use Tagcade\Service\Fetcher\Fetchers\Api\AbstractApiFetcher;

class OpenxApiFetcher extends AbstractApiFetcher
{
	const INTEGRATION_C_NAME = 'open-x';

	/**
	 * @inheritdoc
	 */
	function doGetData(ApiParameterInterface $parameter)
	{
		// TODO: Implement doGetData() method.
	}

	function getIntegrationCName()
	{
		// TODO: Implement getIntegrationCName() method.
	}

	function getColumnNames(array $reports)
	{
		// TODO: Implement getColumnNames() method.
	}

	function getReportValues(array $reports)
	{
		// TODO: Implement getReportValues() method.
	}
}