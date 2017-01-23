<?php

namespace Tagcade\Service\Fetcher\Fetchers\Api;
use Tagcade\Service\Fetcher\ApiParameterInterface;

interface ApiFetcherInterface
{
	/**
	 * Check this fetcher support this integration or not
	 * @param ApiParameterInterface $parameter
	 * @return mixed
	 */
	function supportIntegration(ApiParameterInterface $parameter);

	/**
	 * Get data file
	 * @param ApiParameterInterface $parameter
	 * @return mixed
	 */
	function doGetData(ApiParameterInterface $parameter);

	/**
	 * Get report data
	 * @param $url
	 * @param string $method
	 * @param null $header
	 * @param array $data
	 * @return mixed
	 */
	public function getReport($url, $method='GET', $header = null, $data = array());

	/**
	 * @param array $reports
	 * @return mixed
	 */
	public function getColumnNames(array  $reports);
}