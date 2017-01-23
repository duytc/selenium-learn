<?php

namespace Tagcade\Service\Fetcher\Fetchers\Api\Tagcade;

use DateTime;
use Pheanstalk\Exception;
use RestClient\CurlRestClient;
use Tagcade\Service\Fetcher\ApiParameterInterface;
use Tagcade\Service\Fetcher\Fetchers\Api\AbstractApiFetcher;

class TagcadeApiFetcher extends AbstractApiFetcher
{
	const INTEGRATION_C_NAME = 'tagcade';
	const TOKEN_URL = 'http://api.tagcade.dev/app_debug.php/api/v1/getToken';

	/**
	 * @inheritdoc
	 */
	function doGetData(ApiParameterInterface $parameter)
	{
		$allParams = $parameter->getParams();

		if (!array_key_exists('username', $allParams)) {
			throw new Exception('Missing username in parameters');
		}
		$username = $allParams['username'];

		if (!array_key_exists('password', $allParams)) {
			throw new Exception('Missing password in parameters');
		}
		$password = $allParams['password'];

		if (!array_key_exists('startDate', $allParams)) {
			throw new Exception('Missing startDate in parameters');
		}
		$startDate = $allParams['startDate'];

		$endDate = null;
		if (array_key_exists('endDate', $allParams)) {
			$endDate = $allParams['endDate'];
		};

		if (!array_key_exists('url', $allParams)) {
			throw new Exception('Missing url in parameters');
		}
		$url = $allParams['url'];

		if (!array_key_exists('method', $allParams)) {
			throw new Exception('Missing method in parameters');
		}
		$method = $allParams['method'];

		if (array_key_exists('group', $allParams)) {
			$group = $allParams['group'];
		} else {
			$group = false;
		}

		$token = $this->getToken(self::TOKEN_URL, $username, $password);
		$header = $this->createHeaderData($token);

		$parameterForGetMethod = array('startDate' => $startDate, 'endDate' => $endDate, '$group' => $group);
		$report = $this->getData($url, $method, $header, $parameterForGetMethod);

		if (empty($report)) {
			$this->logger->warning('There are not reports');
			return false;
		}

		$startDate = new DateTime($startDate);
		$endDate = new DateTime($endDate);
		$storeFile = $this->getPath($parameter, $startDate, $endDate, 'downloadFile');
		$report = json_decode($report, true);

		$reportValues = $this->getReportValues($report);
		$header = $this->getColumnNames($reportValues);

		return $this->saveToCSVFile($storeFile, $header, $reportValues);
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

	/**
	 * @inheritdoc
	 */
	function getIntegrationCName()
	{
		return self::INTEGRATION_C_NAME;
	}

	/**
	 * @inheritdoc
	 */
	function getColumnNames(array $reports)
	{
		return array_keys($reports[0]);
	}

	/**
	 * @inheritdoc
	 */
	function getReportValues(array $reports)
	{
		$reportValues = $reports['reports'];

		foreach ($reportValues as $index => $reportValue) {
			foreach ($reportValue as $key => $report) {
				if (is_array($report)) {
					unset($reportValue[$key]);
					$reportValues[$index] = $reportValue;
				}
			}
		}

		return array_values($reportValues);
	}
}