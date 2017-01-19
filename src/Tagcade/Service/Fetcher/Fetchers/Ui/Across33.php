<?php

namespace Tagcade\Service\Fetcher\Fetchers\Ui;

use Facebook\WebDriver\Remote\RemoteWebDriver;
use Tagcade\DataSource\Across33\Across33FetcherInterface;
use Tagcade\Service\Fetcher\ApiParameterInterface;
use Tagcade\Service\Fetcher\Fetchers\ApiFetcher;

class Across33 implements UiFetcherInterface
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

	function supportIntegration(ApiParameterInterface $parameter)
	{
		$allParams = $parameter->getParams();
		$type =  $allParams['type'];
		$integrationCName = $parameter->getIntegrationCName();

		return (($integrationCName == self::INTEGRATION_C_NAME) && ($type == ApiFetcher::TYPE_API));
	}

	function doGetData(ApiParameterInterface $parameter)
	{
		//Todo: Use across33 to fetcher to get data
	}
}