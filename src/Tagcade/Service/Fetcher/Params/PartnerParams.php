<?php

namespace Tagcade\Service\Fetcher\Params;


use Exception;
use Tagcade\Service\Integration\Config;
use Tagcade\Service\Integration\ConfigInterface;

class PartnerParams implements PartnerParamInterface
{
    const PARAM_KEY_USERNAME = 'username';
    const PARAM_KEY_PASSWORD = 'password';
    const PARAM_KEY_START_DATE = 'startDate';
    const PARAM_KEY_END_DATE = 'endDate';
    const PARAM_KEY_DAILY_BREAKDOWN = 'dailyBreakdown';

    const PARAM_KEY_ID = 'id';
    const PARAM_KEY_UUID = 'uuid';
    const PARAM_KEY_DATA_SOURCE = 'dataSource';
    const PARAM_KEY_DATA_SOURCE_ID = 'dataSourceId';
    const PARAM_KEY_INTEGRATION = 'integration';
    const PARAM_KEY_CANONICAL_NAME = 'canonicalName';
    const PARAM_KEY_PUBLISHER = 'publisher';
    const PARAM_KEY_PARAMS = 'params';
    const PARAM_KEY_ORIGINAL_PARAMS = 'originalParams';
    const PARAM_KEY_BACK_FILL_HISTORY = 'backFillHistory';
    const PARAM_KEY_DATA_SOURCE_INTEGRATION_SCHEDULE = 'dataSourceIntegrationSchedule';
    const PARAM_KEY_DATA_SOURCE_INTEGRATION = 'dataSourceIntegration';

    const PARAM_KEY_BACK_FILL = 'backFill';
    const PARAM_KEY_BACK_FILL_START_DATE = 'backFillStartDate';
    const PARAM_KEY_BACK_FILL_END_DATE = 'backFillEndDate';
    const PARAM_KEY_DATA_SOURCE_INTEGRATION_ID = 'dataSourceIntegrationId';
    const PARAM_KEY_DATA_SOURCE_INTEGRATION_SCHEDULE_ID = 'dataSourceIntegrationScheduleId';
    const PARAM_KEY_DATA_SOURCE_INTEGRATION_SCHEDULE_UUID = 'dataSourceIntegrationScheduleUUID';
    const PARAM_KEY_DATA_SOURCE_INTEGRATION_BACKFILL_HISTORY_ID = 'dataSourceIntegrationBackFillHistoryId';

    const PARAM_KEY_DATE_RANGE = 'dateRange';
    const PARAM_KEY_PUBLISHER_ID = 'publisher_id';
    const PARAM_KEY_FETCHER_ACTIVATOR_DATASOURCE_FORCE = 'fetcherActivatorDataSourceForce';

    /* required params (information for webdriver run) */
    /**
     * @var int publisherId
     */
    protected $publisherId;

    /**
     * @var string $integrationCName
     */
    protected $integrationCName;

    /**
     * @var string $dataSourceId
     */
    protected $dataSourceId;

    /**
     * @var int publisherId
     */
    protected $processId;

    /* very common params come from integration */
    /**
     * @var String
     */
    protected $username;
    /**
     * @var String
     */
    protected $password;

    /**
     * @var \DateTime
     */
    protected $startDate;

    /**
     * @var \DateTime
     */
    protected $endDate;

    /** @var  bool */
    protected $dailyBreakdown;
    
    /** @var   */
    protected $backFillStartDate;
    
    /** @var   */
    protected $backFillEndDate;
    
    /** @var   */
    protected $dataSourceIntegrationId;

    /** @var   */
    protected $dataSourceIntegrationScheduleId;

    /** @var   */
    protected $dataSourceIntegrationScheduleUUID;

    /** @var   */
    protected $dataSourceIntegrationBackFillHistoryId;

    /* original integration config */
    /**
     * @var $config
     */
    protected $config;

    protected $backFill;

    protected $fetcherActivatorDataSourceForce;

    public function __construct(ConfigInterface $config)
    {
        /** @var int publisherId */
        $publisherId = $config->getPublisherId();
        /** @var string $integrationCName */
        $integrationCName = $config->getIntegrationCName();
        $dataSourceId = $config->getDataSourceId();
        $processId = getmypid();

        $username = $config->getParamValue(self::PARAM_KEY_USERNAME, null);
        $password = $config->getParamValue(self::PARAM_KEY_PASSWORD, null);

        //// important: try get startDate, endDate by backFill
        if ($config->isNeedRunBackFill()) {
            $startDate = $config->getStartDateFromBackFill();
            $endDate = $config->getEndDateFromBackFill();

            if (!$startDate instanceof \DateTime) {
                throw new Exception('need run backFill but backFillStartDate is invalid');
            }

            $startDateStr = $startDate->format('Y-m-d');

            if ($endDate instanceof \DateTime) {
                $endDateStr = $endDate->format('Y-m-d');
            } else {
                $endDateStr = 'yesterday';
            }

//            $dailyBreakdown = true;
        } else {
            // prefer dateRange than startDate - endDate
            $dateRange = $config->getParamValue(self::PARAM_KEY_DATE_RANGE, null);
            if (!empty($dateRange)) {
                $startDateEndDate = Config::extractDynamicDateRange($dateRange);

                if (!is_array($startDateEndDate)) {
                    // use default 'yesterday'
                    $startDateStr = 'yesterday';
                    $endDateStr = 'yesterday';
                } else {
                    $startDateStr = $startDateEndDate[0];
                    $endDateStr = $startDateEndDate[1];
                }
            } else {
                // use user modified startDate, endDate
                $startDateStr = $config->getParamValue(self::PARAM_KEY_START_DATE, 'yesterday');
                $endDateStr = $config->getParamValue(self::PARAM_KEY_END_DATE, 'yesterday');

                if (empty($startDateStr)) {
                    $startDateStr = 'yesterday';
                }

                if (empty($endDateStr)) {
                    $endDateStr = 'yesterday';
                }
            }
        }

        $configParams = [
            // TODO: remove duplicate definitions of publisherId, integrationCName and processId
            self::PARAM_KEY_PUBLISHER_ID => $publisherId,
            'partner_cname' => $integrationCName,
            'process_id' => $processId,
            self::PARAM_KEY_USERNAME => $username,
            self::PARAM_KEY_PASSWORD => $password,
            self::PARAM_KEY_START_DATE => $startDateStr,
            self::PARAM_KEY_END_DATE => $endDateStr,
        ];

        /* set required params */
        $this->publisherId = $publisherId;
        $this->integrationCName = $integrationCName;
        $this->dataSourceId = $dataSourceId;
        $this->processId = $processId;
        $backFill = $config->getBackFill();

        $this->backFill = $backFill[self::PARAM_KEY_BACK_FILL];
        $this->fetcherActivatorDataSourceForce = $backFill[self::PARAM_KEY_FETCHER_ACTIVATOR_DATASOURCE_FORCE];
        $this->backFillStartDate = $backFill[self::PARAM_KEY_BACK_FILL_START_DATE];
        $this->backFillEndDate = $backFill[self::PARAM_KEY_BACK_FILL_END_DATE];

        $this->dataSourceIntegrationId = $backFill[self::PARAM_KEY_DATA_SOURCE_INTEGRATION_ID];
        $this->dataSourceIntegrationScheduleId = $backFill[self::PARAM_KEY_DATA_SOURCE_INTEGRATION_SCHEDULE_ID];
        if (isset($backFill[self::PARAM_KEY_DATA_SOURCE_INTEGRATION_SCHEDULE_UUID]) && !empty($backFill[self::PARAM_KEY_DATA_SOURCE_INTEGRATION_SCHEDULE_UUID])) {
            $this->dataSourceIntegrationScheduleUUID = $backFill[self::PARAM_KEY_DATA_SOURCE_INTEGRATION_SCHEDULE_UUID];
        }
        $this->dataSourceIntegrationBackFillHistoryId = $backFill[self::PARAM_KEY_DATA_SOURCE_INTEGRATION_BACKFILL_HISTORY_ID];

        /* create common params */
        $this->createParams($configParams);
    }

    /**
     * @var string
     */
    protected $account;

    /**
     * @inheritdoc
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @inheritdoc
     */
    public function setConfig(array $config)
    {
        $this->config = $config;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * @inheritdoc
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @inheritdoc
     */
    public function getStartDate()
    {
        return $this->startDate;
    }

    /**
     * @inheritdoc
     */
    public function setStartDate(\DateTime $startDate)
    {
        $this->startDate = $startDate;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getEndDate()
    {
        return $this->endDate;
    }

    /**
     * @inheritdoc
     */
    public function setEndDate(\DateTime $endDate)
    {
        $this->endDate = $endDate;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function isDailyBreakdown()
    {
        return $this->dailyBreakdown;
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
    public function getIntegrationCName()
    {
        return $this->integrationCName;
    }

    /**
     * @inheritdoc
     */
    public function getDataSourceId()
    {
        return $this->dataSourceId;
    }

    /**
     * @inheritdoc
     */
    public function getProcessId()
    {
        return $this->processId;
    }

    /**
     * create PartnerParams from configs
     *
     * @param array $config
     * @throws \CannotPerformOperationException
     * @throws \InvalidCiphertextException
     * @throws Exception
     */
    protected function createParams(array $config)
    {
        /** @var string $username */
        $username = $config[self::PARAM_KEY_USERNAME];
        $startDate = date_create($config[self::PARAM_KEY_START_DATE]);
        $endDate = date_create($config[self::PARAM_KEY_END_DATE]);

        if ($startDate > $endDate) {
            $clone = clone $startDate;
            $startDate = clone $endDate;
            $endDate = clone $clone;
        }

        if (!array_key_exists('base64EncryptedPassword', $config) && !array_key_exists(self::PARAM_KEY_PASSWORD, $config)) {
            throw new Exception('Invalid configuration. Not found password or base64EncryptedPassword in the configuration');
        }

        if (array_key_exists('base64EncryptedPassword', $config) && !isset($config['publisher']['uuid'])) {
            throw new Exception('Missing key to decrypt publisher password');
        }

        if (array_key_exists('base64EncryptedPassword', $config)) {
            // decrypt the hashed password
            $base64EncryptedPassword = $config['base64EncryptedPassword'];
            $encryptedPassword = base64_decode($base64EncryptedPassword);

            $decryptKey = $this->getEncryptionKey($config['publisher']['uuid']);
            $password = \Crypto::Decrypt($encryptedPassword, $decryptKey);
        } else {
            $password = $config[self::PARAM_KEY_PASSWORD];
        }

        $yesterday = date_create('yesterday');
        $yesterday->setTime(0, 0);

        if ($startDate > $yesterday) {
            $startDate = $yesterday;
        }

        if ($endDate > $yesterday) {
            $endDate =  $yesterday;
        }

        $this->username = $username;
        $this->password = $password;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->config = $config;
    }

    /**
     * get Encryption Key
     *
     * @param $uuid
     * @return string
     */
    protected function getEncryptionKey($uuid)
    {
        $uuid = preg_replace('[\-]', '', $uuid);
        return substr($uuid, 0, 16);
    }

    /**
     * @param boolean $dailyBreakdown
     * @return self
     */
    public function setDailyBreakdown($dailyBreakdown)
    {
        $this->dailyBreakdown = $dailyBreakdown;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getBackFillStartDate()
    {
        return $this->backFillStartDate;
    }

    /**
     * @inheritdoc
     */
    public function setBackFillStartDate($backFillStartDate)
    {
        $this->backFillStartDate = $backFillStartDate;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getBackFillEndDate()
    {
        return $this->backFillEndDate;
    }

    /**
     * @inheritdoc
     */
    public function setBackFillEndDate($backFillEndDate)
    {
        $this->backFillEndDate = $backFillEndDate;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getDataSourceIntegrationId()
    {
        return $this->dataSourceIntegrationId;
    }

    /**
     * @inheritdoc
     */
    public function setDataSourceIntegrationId($dataSourceIntegrationId)
    {
        $this->dataSourceIntegrationId = $dataSourceIntegrationId;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getDataSourceIntegrationScheduleId()
    {
        return $this->dataSourceIntegrationScheduleId;
    }

    /**
     * @inheritdoc
     */
    public function setDataSourceIntegrationScheduleId($dataSourceIntegrationScheduleId)
    {
        $this->dataSourceIntegrationScheduleId = $dataSourceIntegrationScheduleId;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getDataSourceIntegrationScheduleUUID()
    {
        return $this->dataSourceIntegrationScheduleUUID;
    }

    /**
     * @inheritdoc
     */
    public function setDataSourceIntegrationScheduleUUID($dataSourceIntegrationScheduleUUID)
    {
        $this->dataSourceIntegrationScheduleUUID = $dataSourceIntegrationScheduleUUID;
        return $this;
    }
    /**
     * @return mixed
     */
    public function getBackFill()
    {
        return $this->backFill;
    }

    /**
     * @param mixed $backFill
     * @return self
     */
    public function setBackFill($backFill)
    {
        $this->backFill = $backFill;
        
        return $this;
    }

    /**
     * @return mixed
     */
    public function getFetcherActivatorDataSourceForce()
    {
        return $this->fetcherActivatorDataSourceForce;
    }

    /**
     * @param mixed $fetcherActivatorDataSourceForce
     * @return self
     */
    public function setFetcherActivatorDataSourceForce($fetcherActivatorDataSourceForce)
    {
        $this->fetcherActivatorDataSourceForce = $fetcherActivatorDataSourceForce;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getDataSourceIntegrationBackFillHistoryId()
    {
        return $this->dataSourceIntegrationBackFillHistoryId;
    }

    /**
     * @inheritdoc
     */
    public function setDataSourceIntegrationBackFillHistoryId($dataSourceIntegrationBackFillHistoryId)
    {
        $this->dataSourceIntegrationBackFillHistoryId = $dataSourceIntegrationBackFillHistoryId;

        return $this;
    }
}