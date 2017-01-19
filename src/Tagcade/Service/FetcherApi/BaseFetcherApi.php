<?php

namespace Tagcade\Service\FetcherApi;

use Tagcade\Service\FetcherInterface;

class BaseFetcherApi implements FetcherApiInterface
{
	/**
	 * @inheritdoc
	 */
	public function supportType($type)
	{
		return $type = FetcherInterface::TYPE_API;
	}

	/**
	 * @inheritdoc
	 */
	public function execute(array $parameters)
	{
		// TODO: Implement execute() method.
	}

	/**
	 * @param ApiParameterInterface $apiParameter
	 */
	public function supportIntegration(ApiParameterInterface $apiParameter)
	{
		// TODO: Implement supportIntegration() method.
	}
}