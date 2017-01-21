<?php

namespace Tagcade\Service\Fetcher\Fetchers\Api\Tagcade;

use DateTime;
use RestClient\CurlRestClient;
use Tagcade\Service\Fetcher\ApiParameterInterface;
use Tagcade\Service\Fetcher\Fetchers\Api\AbstractApiFetcher;

class TagcadeApiFetcher extends AbstractApiFetcher
{
	const INTEGRATION_C_NAME = 'tagcade';

	/**
	 * @inheritdoc
	 */
	function doGetData(ApiParameterInterface $parameter)
	{
		$allParams = $parameter->getParams();

		$username = $allParams['username'];
		$password = $allParams['password'];
		$startDate = $allParams['startDate'];
		$endDate = $allParams['endDate'];
		$url = $allParams['url'];
		$method = $allParams['method'];
		$group = $allParams['group'];

		$tokenUrl = 'http://api.tagcade.dev/app_debug.php/api/v1/getToken';
		$token = $this->getToken($tokenUrl, $username, $password);
		$header = $this->createHeaderData($token);

		$parameterForGetMethod = array('startDate' => $startDate, 'endDate' => $endDate, '$group' => $group);
		$report = $this->getReport($url, $method, $header, $parameterForGetMethod);

		$startDate = new DateTime($startDate);
		$endDate = new DateTime($endDate);

		$storeFile = $this->getPath($parameter, $startDate, $endDate, 'downloadFile');
		$report = json_decode($report, true);
		//$header = array_keys($report['reports'][0]);

		$this->arrayToCSVFile($storeFile, $report['reports']);

	}

	/**
	 * @inheritdoc
	 */
	protected function getToken($tokenUrl, $username, $password)
	{
		$curl = new CurlRestClient();
		$data = array('username' => $username, 'password' => $password);

		$tokenResponse = $curl->executeQuery($tokenUrl, 'POST', array(), $data);
		$curl->close();
		$token = json_decode($tokenResponse, true);

		if (empty($token)) {
			return null;
		}

		if (json_last_error() !== JSON_ERROR_NONE) {
			throw new \Exception('json decoding for token error');
		}

		if (!array_key_exists('token', $token)) {
			throw new \Exception(sprintf('Could not authenticate user %s', $username));
		}
		$token = $token['token'];

		return $token;
	}

	/**
	 * @inheritdoc
	 */
	protected function createHeaderData($token)
	{
		$header = array('Authorization: Bearer ' . $token);

		return $header;
	}

	function getIntegrationCName()
	{
		return self::INTEGRATION_C_NAME;
	}
}