<?php

namespace Tagcade\Service\Fetcher\Fetchers\Ui;

use Psr\Log\LoggerInterface;
use Tagcade\DataSource\Across33\Across33FetcherInterface;
use Tagcade\Service\Fetcher\ApiParameterInterface;

class Across33 extends AbstractUiFetcher
{
	const INTEGRATION_C_NAME = 'across33';

	/**
	 * Across33 constructor.
	 * @param LoggerInterface $logger
	 * @param Across33FetcherInterface $across33Fetcher
	 */
	public function __construct(LoggerInterface $logger, Across33FetcherInterface $across33Fetcher)
	{
		parent::__construct($logger, $across33Fetcher);
	}

	/**
	 * @inheritdoc
	 */
	function doGetData(ApiParameterInterface $parameter)
	{
		parent::doGetData($parameter);
	}
}