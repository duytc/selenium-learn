<?php

namespace Tagcade\Service\Integration;

use DateTime;
use Exception;

class Config implements ConfigInterface
{
    const PARAM_START_DATE = 'startDate';
    const PARAM_END_DATE = 'endDate';
    const PARAM_DATE_RANGE = 'dateRange';
    const PARAM_DAILY_BREAKDOWN = 'dailyBreakdown';

    /* all define keys-values of params of DataSourceIntegration */
    const PARAM_KEY_KEY = 'key';
    const PARAM_KEY_VALUE = 'value';
    const PARAM_KEY_TYPE = 'type';

    const PARAM_TYPE_PLAIN_TEXT = 'plainText'; // e.g username, url, ...
    const PARAM_TYPE_DATE = 'date'; // e.g startDate, ...
    const PARAM_TYPE_DYNAMIC_DATE_RANGE = 'dynamicDateRange'; // e.g dateRange, ...
    const PARAM_TYPE_SECURE = 'secure'; // e.g password, token, key, ...
    const PARAM_TYPE_REGEX = 'regex'; // e.g pattern, ...
    const PARAM_TYPE_OPTION = 'option'; // e.g pattern, ...
    const PARAM_TYPE_BOOL = 'bool'; // e.g true, false ...

    const PARAM_VALUE_DYNAMIC_DATE_RANGE_YESTERDAY = 'yesterday';
    const PARAM_VALUE_DYNAMIC_DATE_RANGE_LAST_2_DAYS = 'last 2 days';
    const PARAM_VALUE_DYNAMIC_DATE_RANGE_LAST_3_DAYS = 'last 3 days';
    const PARAM_VALUE_DYNAMIC_DATE_RANGE_LAST_4_DAYS = 'last 4 days';
    const PARAM_VALUE_DYNAMIC_DATE_RANGE_LAST_5_DAYS = 'last 5 days';
    const PARAM_VALUE_DYNAMIC_DATE_RANGE_LAST_6_DAYS = 'last 6 days';
    const PARAM_VALUE_DYNAMIC_DATE_RANGE_LAST_WEEK = 'last week';

    /* all define keys-values of backfill feature of DataSource */
    const DATA_SOURCE_BACKFILL = 'backFill';
    const DATA_SOURCE_BACKFILL_START_DATE = 'backFillStartDate';
    const DATA_SOURCE_BACKFILL_EXECUTED = 'backFillExecuted';

    /* supported params types */
    public static $SUPPORTED_PARAM_TYPES = [
        self::PARAM_TYPE_PLAIN_TEXT,
        self::PARAM_TYPE_DATE,
        self::PARAM_TYPE_DYNAMIC_DATE_RANGE,
        self::PARAM_TYPE_SECURE,
        self::PARAM_TYPE_REGEX
    ];

    /* supported dynamic date range values */
    public static $SUPPORTED_PARAM_VALUE_DYNAMIC_DATE_RANGES = [
        self::PARAM_VALUE_DYNAMIC_DATE_RANGE_YESTERDAY => '-1 day',
        self::PARAM_VALUE_DYNAMIC_DATE_RANGE_LAST_2_DAYS => '-2 day',
        self::PARAM_VALUE_DYNAMIC_DATE_RANGE_LAST_3_DAYS => '-3 day',
        self::PARAM_VALUE_DYNAMIC_DATE_RANGE_LAST_4_DAYS => '-4 day',
        self::PARAM_VALUE_DYNAMIC_DATE_RANGE_LAST_5_DAYS => '-5 day',
        self::PARAM_VALUE_DYNAMIC_DATE_RANGE_LAST_6_DAYS => '-6 day',
        self::PARAM_VALUE_DYNAMIC_DATE_RANGE_LAST_WEEK => '-7 day'
    ];

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
            if (!is_array($param) || !array_key_exists(self::PARAM_KEY_KEY, $param)) {
                continue;
            }

            if ($param[self::PARAM_KEY_KEY] === $paramKey) {
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
        if (!is_array($paramArr) || !array_key_exists(self::PARAM_KEY_VALUE, $paramArr) || $paramArr[self::PARAM_KEY_VALUE] === null) {
            return $defaultValue;
        }

        $value = $paramArr[self::PARAM_KEY_VALUE];

        $type = $this->getParamType($paramKey, null);

        // decode value (base64) if type is 'secure'
        $value = ($type === self::PARAM_TYPE_SECURE) ? base64_decode($value) : $value;

        // build full regex if type is 'regex'. Default we support flag 'i' for case insensitive
        $value = ($type === self::PARAM_TYPE_REGEX) ? sprintf('/%s/i', $value) : $value;

        // set param type to true|false if type is 'bool'
        $value = ($type === self::PARAM_TYPE_BOOL) ? (strtolower($value) == 'true' || strtolower($value) == '1' ? true : false) : $value;

        return $value;
    }

    /**
     * @inheritDoc
     */
    public function getParamType($paramKey, $defaultValue)
    {
        $paramArr = $this->getParamArr($paramKey);
        if (!is_array($paramArr) || !array_key_exists(self::PARAM_KEY_TYPE, $paramArr)) {
            return $defaultValue;
        }

        return $paramArr[self::PARAM_KEY_TYPE];
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
        if (!is_array($this->backFill)
            || !array_key_exists(self::DATA_SOURCE_BACKFILL, $this->backFill)
            || !array_key_exists(self::DATA_SOURCE_BACKFILL_EXECUTED, $this->backFill)
        ) {
            return false;
        }

        $isBackFill = (bool)$this->backFill[self::DATA_SOURCE_BACKFILL];
        $isBackFillExecuted = (bool)$this->backFill[self::DATA_SOURCE_BACKFILL_EXECUTED];

        return $isBackFill && !$isBackFillExecuted;
    }

    /**
     * @inheritDoc
     */
    public function getStartDateFromBackFill()
    {
        if (!is_array($this->backFill) || !array_key_exists(self::DATA_SOURCE_BACKFILL_START_DATE, $this->backFill)) {
            return false;
        }

        $backFillStartDateString = $this->backFill[self::DATA_SOURCE_BACKFILL_START_DATE];

        try {
            $backFillStartDate = date_create($backFillStartDateString);

            return $backFillStartDate;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * extract dynamic date range from dateRange value
     *
     * @param string $dynamicDateRange
     * @return bool|array [ <startDate>, <endDate> ]
     */
    public static function extractDynamicDateRange($dynamicDateRange)
    {
        if (!array_key_exists($dynamicDateRange, self::$SUPPORTED_PARAM_VALUE_DYNAMIC_DATE_RANGES)) {
            return false;
        }

        try {
            $startDate = date('Y-m-d', strtotime(self::$SUPPORTED_PARAM_VALUE_DYNAMIC_DATE_RANGES[$dynamicDateRange]));
            $endDate = date('Y-m-d', strtotime('-1 day'));
        } catch (Exception $e) {
            return false;
        }

        return [$startDate, $endDate];
    }

    /**
     * @inheritdoc
     */
    public function getStartDateEndDate()
    {
        //// important: try get startDate, endDate by backFill
        if ($this->isNeedRunBackFill()) {
            $startDate = $this->getStartDateFromBackFill();

            if (!$startDate instanceof DateTime) {
                throw new Exception('need run backFill but backFillStartDate is invalid');
            }

            $endDate = new DateTime('yesterday');
        } else {
            // prefer dateRange than startDate - endDate
            $dateRange = $this->getParamValue(self::PARAM_DATE_RANGE, null);
            if (!empty($dateRange)) {
                $startDateEndDate = Config::extractDynamicDateRange($dateRange);

                if (!is_array($startDateEndDate)) {
                    // use default 'yesterday'
                    $startDate = new DateTime('yesterday');
                    $endDate = new DateTime('yesterday');
                } else {
                    $startDate = new DateTime($startDateEndDate[0]);
                    $endDate = new DateTime($startDateEndDate[1]);
                }
            } else {
                // use user modified startDate, endDate
                $startDate = new DateTime($this->getParamValue(self::PARAM_START_DATE, 'yesterday'));
                $endDate = new DateTime($this->getParamValue(self::PARAM_END_DATE, 'yesterday'));
            }
        }

        return [
            self::PARAM_START_DATE => $startDate,
            self::PARAM_END_DATE => $endDate,
        ];
    }
}