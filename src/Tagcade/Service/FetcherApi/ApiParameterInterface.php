<?php

namespace Tagcade\Service\FetcherApi;

interface ApiParameterInterface
{
	/**
	 * @return mixed
	 */
	public function getPublisherId();

	/**
	 * @param mixed $publisherId
	 */
	public function setPublisherId($publisherId);

	/**
	 * @return mixed
	 */
	public function getIntegrationCName();

	/**
	 * @param mixed $integrationCName
	 */
	public function setIntegrationCName($integrationCName);


	/**
	 * @return array
	 */
	public function getParams();

	/**
	 * @param array $params
	 */
	public function setParams($params);

}