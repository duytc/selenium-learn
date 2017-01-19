<?php

namespace Tagcade\Service\Fetcher\Fetchers\Ui;

use Tagcade\Service\Fetcher\ApiParameterInterface;
use Tagcade\Service\Fetcher\Fetchers\ApiFetcher;

abstract class AbstractUiFetcher implements UiFetcherInterface
{
	const INTEGRATION_C_NAME = null;

	/**
	 * @inheritdoc
	 */
	function supportIntegration(ApiParameterInterface $parameter)
	{
		$allParams = $parameter->getParams();
		$type = $allParams['type'];
		$integrationCName = $parameter->getIntegrationCName();

		return (($integrationCName == self::INTEGRATION_C_NAME) && ($type == ApiFetcher::TYPE_UI));
	}
}