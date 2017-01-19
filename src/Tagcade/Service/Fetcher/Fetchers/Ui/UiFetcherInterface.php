<?php

namespace Tagcade\Service\Fetcher\Fetchers\Ui;

use Tagcade\Service\Fetcher\ApiParameterInterface;

interface UiFetcherInterface
{
	const TYPE = 'api';

	function supportIntegration(ApiParameterInterface $parameter);
	function doGetData(ApiParameterInterface $parameter);
}