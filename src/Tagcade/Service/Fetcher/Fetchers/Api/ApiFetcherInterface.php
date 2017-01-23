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
	 * Get all data
	 * @param $url
	 * @param string $method
	 * @param null $header
	 * @param array $data
	 * @return mixed
	 */
	public function getData($url, $method='GET', $header = null, $data = array());

	/**
	 * Get all column names
	 * @param array $data
	 * @return mixed
	 */
	public function getColumnNames(array  $data);

	/**
	 * Get report values
	 * @param array $data
	 * @return mixed
	 */
	public function getReportValues(array $data);

}