<?php

namespace Tagcade\Service\Fetcher\Params;


use Tagcade\Service\Integration\Config;
use Tagcade\Service\Integration\ConfigInterface;

class PartnerParams implements PartnerParamInterface
{
    const PARAM_KEY_USERNAME = 'username';
    const PARAM_KEY_PASSWORD = 'password';
    const PARAM_KEY_START_DATE = 'startDate';
    const PARAM_KEY_END_DATE = 'endDate';

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

    /* original integration config */
    /**
     * @var $config
     */
    protected $config;

    public function __construct(ConfigInterface $config)
    {
        /** @var int publisherId */
        $publisherId = $config->getPublisherId();
        /** @var string $integrationCName */
        $integrationCName = $config->getIntegrationCName();
        $processId = getmypid();

        $username = $config->getParamValue(self::PARAM_KEY_USERNAME, null);
        $password = $config->getParamValue(self::PARAM_KEY_PASSWORD, null);

        //// important: try get startDate, endDate by backFill
        if ($config->isNeedRunBackFill()) {
            $startDate = $config->getStartDateFromBackFill();

            if (!$startDate instanceof \DateTime) {
                throw new \Exception('need run backFill but backFillStartDate is invalid');
            }

            $startDateStr = $startDate->format('Y-m-d');
            $endDateStr = 'yesterday';
        } else {
            // prefer dateRange than startDate - endDate
            $dateRange = $config->getParamValue('dateRange', null);
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
            'publisher_id' => $publisherId,
            'partner_cname' => $integrationCName,
            'process_id' => $processId,
            self::PARAM_KEY_USERNAME => $username,
            self::PARAM_KEY_PASSWORD => $password,
            self::PARAM_KEY_START_DATE => $startDateStr,
            self::PARAM_KEY_END_DATE => $endDateStr
        ];

        /* set required params */
        $this->publisherId = $publisherId;
        $this->integrationCName = $integrationCName;
        $this->processId = $processId;

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
    public function getEndDate()
    {
        return $this->endDate;
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
     * @throws \Exception
     */
    protected function createParams(array $config)
    {
        /** @var string $username */
        $username = $config[self::PARAM_KEY_USERNAME];
        $startDate = date_create($config[self::PARAM_KEY_START_DATE]);
        $endDate = date_create($config[self::PARAM_KEY_END_DATE]);

        if ($startDate > $endDate) {
            throw new \InvalidArgumentException(sprintf('Invalid date range startDate=%s, endDate=%s', $startDate->format('Ymd'), $endDate->format('Ymd')));
        }

        if (!array_key_exists('base64EncryptedPassword', $config) && !array_key_exists(self::PARAM_KEY_PASSWORD, $config)) {
            throw new \Exception('Invalid configuration. Not found password or base64EncryptedPassword in the configuration');
        }

        if (array_key_exists('base64EncryptedPassword', $config) && !isset($config['publisher']['uuid'])) {
            throw new \Exception('Missing key to decrypt publisher password');
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
}