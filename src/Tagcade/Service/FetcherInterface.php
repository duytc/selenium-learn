<?php

namespace Tagcade\Service;

use Tagcade\Service\FetcherApi\ApiParameterInterface;

interface FetcherInterface
{
	/**
	 * @param ApiParameterInterface $apiParameter
	 */
	public function supportIntegration(ApiParameterInterface $apiParameter);

	public function execute(array $parameters);

}