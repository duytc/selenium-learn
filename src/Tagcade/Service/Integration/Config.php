<?php

namespace Tagcade\Service\Integration;

class Config implements ConfigInterface
{
    private $publisherId;
    private $integrationCName;
    private $dataSourceId;
    /** @var array */
    private $params;
    /** @var array */
    private $backFill;

    /**
     * ApiParameter constructor.
     * @param $publisherId
     * @param $integrationCName
     * @param $dataSourceId
     * @param array $params
     * @param array $backFill
     */
    public function __construct($publisherId, $integrationCName, $dataSourceId, array $params, array $backFill)
    {
        $this->publisherId = $publisherId;
        $this->integrationCName = $integrationCName;
        $this->dataSourceId = $dataSourceId;
        $this->params = $params;
        $this->backFill = $backFill;
    }

    /**
     * @inheritdoc
     */
    public function getPublisherId(): int
    {
        return $this->publisherId;
    }

    /**
     * @inheritdoc
     */
    public function setPublisherId(int $publisherId)
    {
        $this->publisherId = $publisherId;
    }

    /**
     * @inheritdoc
     */
    public function getIntegrationCName(): string
    {
        return $this->integrationCName;
    }

    /**
     * @inheritdoc
     */
    public function setIntegrationCName(string $integrationCName)
    {
        $this->integrationCName = $integrationCName;
    }

    /**
     * @inheritDoc
     */
    public function getDataSourceId(): int
    {
        return $this->dataSourceId;
    }

    /**
     * @inheritDoc
     */
    public function setDataSourceId(int $dataSourceId)
    {
        $this->dataSourceId = $dataSourceId;
    }

    /**
     * @inheritdoc
     */
    public function getParams(): array
    {
        return $this->params;
    }

    /**
     * @inheritDoc
     */
    public function getParamArr($paramKey)
    {
        if (!is_string($paramKey) || empty($paramKey)) {
            return false;
        }

        foreach ($this->params as $param) {
            if (!is_array($param) || !array_key_exists('key', $param)) {
                continue;
            }

            if ($param['key'] === $paramKey) {
                return $param;
            }
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    public function getParamValue($paramKey, $defaultValue)
    {
        $paramArr = $this->getParamArr($paramKey);
        if (!is_array($paramArr) || !array_key_exists('value', $paramArr)) {
            return $defaultValue;
        }

        $value = $paramArr['value'];

        // decode value (base64) if type is 'secure'
        $type = $this->getParamType($paramKey, null);

        return ($type === 'secure') ? base64_decode($value) : $value;
    }

    /**
     * @inheritDoc
     */
    public function getParamType($paramKey, $defaultValue)
    {
        $paramArr = $this->getParamArr($paramKey);
        if (!is_array($paramArr) || !array_key_exists('type', $paramArr)) {
            return $defaultValue;
        }

        return $paramArr['type'];
    }

    /**
     * @inheritdoc
     */
    public function setParams(array $params)
    {
        $this->params = $params;
    }

    /**
     * @inheritdoc
     */
    public function getBackFill()
    {
        return $this->backFill;
    }

    /**
     * @inheritdoc
     */
    public function setBackFill(array $backFill)
    {
        $this->backFill = $backFill;
    }

    /**
     * @inheritDoc
     */
    public function isNeedRunBackFill()
    {
        if (is_array($this->backFill) || !array_key_exists('backFill', $this->backFill)) {
            return false;
        }

        return (bool)$this->backFill['backFill'];
    }

    /**
     * @inheritDoc
     */
    public function getStartDateFromBackFill()
    {
        if (is_array($this->backFill) || !array_key_exists('backFillStartDate', $this->backFill)) {
            return false;
        }

        $backFillStartDateString = $this->backFill['backFill'];

        try {
            $backFillStartDate = date_create_from_format('Y-m-d', $backFillStartDateString);

            return $backFillStartDate;
        } catch (\Exception $e) {
            return false;
        }
    }
}