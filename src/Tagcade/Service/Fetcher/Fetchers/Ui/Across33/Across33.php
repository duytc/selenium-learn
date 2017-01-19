<?php

namespace Tagcade\Service\Fetcher\Fetchers\Ui;

use Tagcade\DataSource\Across33\Across33FetcherInterface;
use Tagcade\Service\Fetcher\ApiParameterInterface;

class Across33 extends AbstractUiFetcher
{
	const INTEGRATION_C_NAME = 'across33';
	/**
	 * @var Across33FetcherInterface
	 */
	private $across33Fetcher;

	/**
	 * Across33 constructor.
	 * @param Across33FetcherInterface $across33Fetcher
	 */
	public function __construct(Across33FetcherInterface $across33Fetcher)
	{
		$this->across33Fetcher = $across33Fetcher;
	}

	/**
	 * @inheritdoc
	 */
	function doGetData(ApiParameterInterface $parameter)
	{
		//Todo: Use across33 to fetcher to get data
	}
}