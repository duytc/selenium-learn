<?php

namespace Tagcade\Service\Fetcher\Fetchers\Api\Tagcade;

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
		$group =  $allParams['group'];

		$publisherId = $parameter->getPublisherId();

		$data = array('username' => $username, 'password' => $password);
		$curl = new CurlRestClient();
		$urlAuthen = 'http://api.tagcade.dev/app_debug.php/api/v1/getToken';
		$tokenResponse = $curl->executeQuery($urlAuthen, 'POST', array(), $data);

		$curl->close();
		$token = json_decode($tokenResponse, true);

		if (empty($token)) {
			var_dump('Empty token');
			var_dump($token);
			return null;
		}

		if (json_last_error() !== JSON_ERROR_NONE) {
			throw new \Exception('json decoding for token error');
		}
		if (!array_key_exists('token', $token)) {
			throw new \Exception(sprintf('Could not authenticate user %s', $username));
		}

		$token = $token['token'];
		$header = array('Authorization: Bearer ' . $token);

		$reportData = array('startDate' => $startDate, 'endDate' => $endDate, '$group'=>$group);
		$responseData = $curl->executeQuery($url, 'GET', $header, $reportData);
		$curl->close();

	}
}