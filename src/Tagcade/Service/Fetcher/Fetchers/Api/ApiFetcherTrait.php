<?php

namespace Tagcade\Service\Fetcher\Fetchers\Api;

use DateTime;
use RestClient\CurlRestClient;
use Tagcade\Service\Fetcher\ApiParameterInterface;
use Tagcade\Service\Fetcher\Fetchers\ApiFetcher;

trait ApiFetcherTrait
{
	/**
	 * @inheritdoc
	 */
	function supportIntegration(ApiParameterInterface $parameter)
	{
		$allParams = $parameter->getParams();
		$type = $allParams['type'];
		$integrationCName = $parameter->getIntegrationCName();

		return (($integrationCName == self::INTEGRATION_C_NAME) && ($type == ApiFetcher::TYPE_API));
	}

	/**
	 * @inheritdoc
	 */
	public function getReport($url, $method = 'GET', $header = null, $data = array())
	{
		$curl = new CurlRestClient();
		$responseData = $curl->executeQuery($url, $method, $header, $data);
		$curl->close();

		return $responseData;
	}

	/**
	 * @param ApiParameterInterface $parameter
	 * @param DateTime $startDate
	 * @param DateTime $endDate
	 * @param $fileName
	 * @param $rootDirectory
	 * @return string
	 */
	public function getPath(ApiParameterInterface $parameter, DateTime $startDate, DateTime $endDate, $fileName, $rootDirectory)
	{
		$publisherId = $parameter->getPublisherId();
		$partnerCName = $parameter->getIntegrationCName();

		$RunningCommandDate = new \DateTime('now');
		$myProcessId = getmypid();

		if (!is_dir($rootDirectory)) {
			mkdir($rootDirectory);
		}

		$publisherPath = sprintf('%s/%s', realpath($rootDirectory), $publisherId);
		if (!is_dir($publisherPath)) {
			mkdir($publisherPath);
		}

		$partnerPath = $tmpPath = sprintf('%s/%s', $publisherPath, $partnerCName);
		if (!is_dir($partnerPath)) {
			mkdir($partnerPath);
		}

		$directory = sprintf('%s/%s-%s-%s-%s', $partnerPath, $RunningCommandDate->format('Ymd'), $startDate->format('Ymd'), $endDate->format('Ymd'), $myProcessId);
		if (!is_dir($directory)) {
			mkdir($directory);
		}

		$path = sprintf('%s/%s.csv', $directory, $fileName);

		$extension = 1;
		while (file_exists($path)) {
			$path = sprintf('%s/%s(%d).csv', $directory, $fileName, $extension);
			$extension++;
		}

		return $path;
	}
}