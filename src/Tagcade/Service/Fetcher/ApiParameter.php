<?php

namespace Tagcade\Service\Fetcher;

class ApiParameter implements ApiParameterInterface
{
	private $publisherId;
	private $integrationCName;
	/**
	 * @var array
	 */
	private $params;

	/**
	 * ApiParameter constructor.
	 * @param $publisherId
	 * @param $integrationCName
	 * @param array $params
	 */
	public function __construct($publisherId, $integrationCName, array  $params)
	{

		$this->publisherId = $publisherId;
		$this->integrationCName = $integrationCName;
		$this->params = $params;
	}

	/**
	 * @inheritdoc
	 */
	public function getPublisherId()
	{
		return $this->publisherId;
	}

	/**
	 * @inheritdoc
	 */
	public function setPublisherId($publisherId)
	{
		$this->publisherId = $publisherId;
	}

	/**
	 * @inheritdoc
	 */
	public function getIntegrationCName()
	{
		return $this->integrationCName;
	}

	/**
	 * @inheritdoc
	 */
	public function setIntegrationCName($integrationCName)
	{
		$this->integrationCName = $integrationCName;
	}

	/**
	 * @inheritdoc
	 */
	public function getParams()
	{
		return $this->params;
	}

	/**
	 * @inheritdoc
	 */
	public function setParams($params)
	{
		$this->params = $params;
	}

}