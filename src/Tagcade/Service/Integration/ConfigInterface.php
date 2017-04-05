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
     * get param array by param key
     *
     * @param string $paramKey
     * @return array|bool false if paramKey empty or is not string or not found
     */
    public function getParamArr($paramKey);

    /**
     * get param value by param key, also decode base64 if type is 'secure'
     *
     * @param string $paramKey
     * @param mixed $defaultValue
     * @return mixed found value or defaultValue if not found
     */
    public function getParamValue($paramKey, $defaultValue);

    /**
     * get param type by param key
     *
     * @param string $paramKey
     * @param mixed $defaultType
     * @return mixed found value or defaultValue if not found
     */
    public function getParamType($paramKey, $defaultType);

    /**
     * @param array $params
     * @return self
     */
    public function setParams(array $params);

    /**
     * @return array
     */
    public function getBackFill();

    /**
     * @param array $backFill
     */
    public function setBackFill(array $backFill);

    /**
     * @return bool
     */
    public function isNeedRunBackFill();

    /**
     * @return \DateTime|false
     */
    public function getStartDateFromBackFill();

    /**
     * @return array [ startDate => <startDate>, endDate => <endDate> ]
     */
    public function getStartDateEndDate();
}