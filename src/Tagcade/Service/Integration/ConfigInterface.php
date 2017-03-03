<?php

namespace Tagcade\Service\Integration;

interface ConfigInterface
{
    /**
     * @return int
     */
    public function getPublisherId(): int;

    /**
     * @param int $publisherId
     * @return self
     */
    public function setPublisherId(int $publisherId);

    /**
     * @return string
     */
    public function getIntegrationCName() : string;

    /**
     * @param string $integrationCName
     * @return self
     */
    public function setIntegrationCName(string $integrationCName);

    /**
     * @return int
     */
    public function getDataSourceId(): int;

    /**
     * @param int $dataSourceId
     * @return self
     */
    public function setDataSourceId(int $dataSourceId);

    /**
     * @return array
     */
    public function getParams(): array;

    /**
     * @param array $params
     * @return self
     */
    public function setParams(array $params);
}