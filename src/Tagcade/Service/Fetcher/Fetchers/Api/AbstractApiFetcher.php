<?php

namespace Tagcade\Service\Fetcher\Fetchers\Api;

use DateTime;
use Psr\Log\LoggerInterface;
use RestClient\CurlRestClient;
use Tagcade\Service\Fetcher\ApiParameterInterface;

abstract class AbstractApiFetcher implements ApiFetcherInterface
{
	const INTEGRATION_C_NAME = null;

	protected $rootDirectory;
	protected $logger;
	/**
	 * @var
	 */
	private $rootKernelDirectory;
	/**
	 * @var
	 */
	private $dataDirectory;

	/**
	 * AbstractApiFetcher constructor.
	 * @param $dataDirectory
	 * @param $rootKernelDirectory
	 * @param LoggerInterface $logger
	 * @internal param string $rootDirectory
	 */
	public function __construct($dataDirectory, $rootKernelDirectory, LoggerInterface $logger)
	{
		$this->dataDirectory = $dataDirectory;
		$this->rootKernelDirectory = $rootKernelDirectory;
		$this->logger = $logger;
	}

	/**
	 * @return string
	 */
	public function getRootDirectory()
	{
		$dataPath = $this->dataDirectory;
		$isRelativeToProjectRootDir = (strpos($dataPath, './') === 0 || strpos($dataPath, '/') !== 0);
		$dataPath = $isRelativeToProjectRootDir ? sprintf('%s/%s', rtrim($this->rootKernelDirectory, '/app'), ltrim($dataPath, './')) : $dataPath;

		return $dataPath;
	}

	/**
	 * @inheritdoc
	 */
	function supportIntegration(ApiParameterInterface $parameter)
	{
		return ($parameter->getIntegrationCName() == $this->getIntegrationCName());
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
	 * @return string
	 */
	public function getPath(ApiParameterInterface $parameter, DateTime $startDate, DateTime $endDate, $fileName)
	{
		$rootDirectory = $this->getRootDirectory() ? $this->getRootDirectory() : './data';
		$publisherId = $parameter->getPublisherId();
		$partnerCName = $parameter->getIntegrationCName();

		$RunningCommandDate = new \DateTime('now');
		$myProcessId = getmypid();

		if (!is_dir($rootDirectory)) {
			mkdir($rootDirectory);
		}

		$publisherPath = sprintf('%s/%s', $rootDirectory, $publisherId);
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

	/**
	 * @param $path
	 * @param $dataRows
	 * @throws \Exception
	 */
	public function arrayToCSVFile($path, $dataRows)
	{
		if (is_dir($path)) {
			throw new \Exception ('Path must be file');
		}

		if (!is_array($dataRows)) {
			throw new \Exception ('Data to save csv file expect array type');
		}

		$file = fopen($path, 'w');
		foreach ($dataRows as $dataRow) {

			$dataRow = array_filter($dataRow, function ($column) {
				return !is_array($column);
			});

			fputcsv($file, $dataRow);
		}

		fclose($file);
	}

	abstract function getIntegrationCName();
	abstract function getColumnNames(array $reports);
	abstract function getReportValues(array $reports);
}