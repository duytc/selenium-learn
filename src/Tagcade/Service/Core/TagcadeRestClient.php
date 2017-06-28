<?php

namespace Tagcade\Service\Core;

use DateTime;
use Exception;
use Psr\Log\LoggerInterface;
use RestClient\CurlRestClient;
use Tagcade\Service\Fetcher\Params\PartnerParamInterface;
use Tagcade\Service\Fetcher\Params\PartnerParams;

class TagcadeRestClient implements TagcadeRestClientInterface
{
    const DEBUG = 0;
    const PENDING = 'pending';
    const EXECUTED_AT = 'executedAt';

    /** @var string */
    private $username;

    /** @var array */
    private $password;

    /** @var CurlRestClient */
    private $curl;

    /** @var string */
    private $getTokenUrl;

    /** @var string */
    private $getListPublisherUrl;

    /** @var string */
    private $getListIntegrationsToBeExecutedUrl;

    /** @var string */
    private $getListIntegrationsByDataSourceIdUrl;

    /** @var string */
    private $updateNextExecuteAtForScheduleUrl;

    /** @var string */
    private $urCreateAlertUrl;

    /** @var string */
    private $token;

    /** @var LoggerInterface */
    private $logger;

    /** @var string */
    private $updateScheduleUrl;
    /** @var string */
    private $updateBackFillHistoryUrl;

    function __construct(CurlRestClient $curl, $username, $password,
                         $getTokenUrl,
                         $getListPublisherUrl,
                         $getListIntegrationsToBeExecutedUrl,
                         $getListIntegrationsByDataSourceIdUrl,
                         $updateNextExecuteAtForScheduleUrl,
                         $urCreateAlertUrl,
                         $updateScheduleUrl,
                         $updateBackFillHistoryUrl
    )
    {
        $this->curl = $curl;
        $this->username = $username;
        $this->password = $password;

        $this->getTokenUrl = $getTokenUrl;
        $this->getListPublisherUrl = $getListPublisherUrl;
        $this->getListIntegrationsToBeExecutedUrl = $getListIntegrationsToBeExecutedUrl;
        $this->getListIntegrationsByDataSourceIdUrl = $getListIntegrationsByDataSourceIdUrl;
        $this->updateNextExecuteAtForScheduleUrl = $updateNextExecuteAtForScheduleUrl;
        $this->urCreateAlertUrl = $urCreateAlertUrl;
        $this->updateScheduleUrl = $updateScheduleUrl;
        $this->updateBackFillHistoryUrl = $updateBackFillHistoryUrl;
    }

    /**
     * @return LoggerInterface
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * @param LoggerInterface $logger
     */
    public function setLogger($logger)
    {
        $this->logger = $logger;
    }

    public function getToken($force = false)
    {
        if ($this->token != null && $force == false) {
            return $this->token;
        }

        $this->logger->info('Trying to get token');

        $data = array('username' => $this->username, 'password' => $this->password);
        $tokenResponse = $this->curl->executeQuery($this->getTokenUrl, 'POST', array(), $data);
        $this->curl->close();
        $token = json_decode($tokenResponse, true);

        if (empty($token)) {
            $this->logger->error(sprintf('Cannot get token with returned message: %s', $tokenResponse));

            return null;
        }

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('json decoding for token error');
        }
        if (!array_key_exists('token', $token)) {
            throw new Exception(sprintf('Could not authenticate user %s', $this->username));
        }

        $this->token = $token['token'];

        $this->logger->info(sprintf('Got token %s', $this->token));

        return $this->token;
    }

    public function getPartnerConfigurationForAllPublishers($partnerCName, $publisherId = null)
    {
        $this->logger->info(sprintf('Getting publisher configuration for partner %s', $partnerCName));

        $header = array('Authorization: Bearer ' . $this->getToken());

        $data = is_numeric($publisherId) ? [PartnerParams::PARAM_KEY_PUBLISHER => $publisherId] : [];
        $publishers = $this->curl->executeQuery(
            str_replace('{cname}', $partnerCName, $this->getListPublisherUrl),
            'GET',
            $header,
            $data
        );

        $this->curl->close();

        $this->logger->info(sprintf('finished getting publisher configuration. Got this %s', $publishers));

        return json_decode($publishers, true);
    }

    /**
     * @inheritdoc
     */
    public function getDataSourceIntegrationSchedulesToBeExecuted($dataSourceId = null)
    {
        $this->logger->info(sprintf('Getting all Integrations to be executed'));

        /* get token */
        $header = array('Authorization: Bearer ' . $this->getToken());

        /* get from ur api */
        $data = [];
        $dataSourceIntegrations = $this->curl->executeQuery(
            $this->getListIntegrationsToBeExecutedUrl,
            'GET',
            $header,
            $data
        );

        $this->curl->close();

        /* decode and parse */
        $result = json_decode($dataSourceIntegrations, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->logger->error(sprintf('Invalid response (json decode failed)'));
            return false;
        }

        if (array_key_exists('code', $result) && $result['code'] != 200) {
            $this->logger->error(sprintf('Not found Integration to be executed'));
            return false;
        }

        if ($dataSourceId) {
            $result = array_filter($result, function ($dataSourceIntegrationSchedule) use ($dataSourceId) {
                if ($dataSourceIntegrationSchedule['dataSourceIntegrationSchedule']['dataSourceIntegration'][PartnerParams::PARAM_KEY_DATA_SOURCE]['id'] == $dataSourceId) {
                    return true;
                }

                if ($dataSourceIntegrationSchedule['backFillHistory']['dataSourceIntegration'][PartnerParams::PARAM_KEY_DATA_SOURCE]['id'] == $dataSourceId) {
                    return true;
                }

                return false;
            });
        }

        $this->logger->info(sprintf('Found %d Integrations to be executed', count($result)));

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function getDataSourceIntegrationSchedulesByDataSource($dataSourceId)
    {
        $this->logger->info(sprintf('Getting all Integrations to be executed'));

        /* get token */
        $header = array('Authorization: Bearer ' . $this->getToken());

        /* get from ur api */
        $data = [
            PartnerParams::PARAM_KEY_DATA_SOURCE => $dataSourceId
        ];
        $url = $this->getListIntegrationsByDataSourceIdUrl;
        $dataSourceIntegrationSchedules = $this->curl->executeQuery(
            $url,
            'GET',
            $header,
            $data
        );

        $this->curl->close();

        /* decode and parse */
        $result = json_decode($dataSourceIntegrationSchedules, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->logger->error(sprintf('Invalid response (json decode failed)'));
            return false;
        }

        if (array_key_exists('code', $result) && $result['code'] != 200) {
            $this->logger->error(sprintf('Not found Integration to be executed'));
            return false;
        }

        $this->logger->info(sprintf('Found %d Integrations to be executed', count($result)));

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function createAlertWhenLoginFail($publisherId, $integrationCName, $dataSourceId, DateTime $startDate, DateTime $endDate, DateTime $executionDate)
    {
        $this->logger->info(sprintf('Creating an alert login fail for Integration %s', $integrationCName));

        $header = array('Authorization: Bearer ' . $this->getToken());

        $data = [
            'code' => 2001,
            'detail' => [
                'integrationName' => $integrationCName,
                'integrationCName' => $integrationCName,
                PartnerParams::PARAM_KEY_DATA_SOURCE_ID => $dataSourceId,
                PartnerParams::PARAM_KEY_START_DATE => $startDate->format('Y-m-d'),
                PartnerParams::PARAM_KEY_END_DATE => $endDate->format('Y-m-d'),
                'executionDate' => $executionDate->format('Y-m-d')
            ],
            PartnerParams::PARAM_KEY_DATA_SOURCE => $dataSourceId,
            PartnerParams::PARAM_KEY_PUBLISHER => $publisherId
        ];

        $result = $this->curl->executeQuery(
            $this->urCreateAlertUrl,
            'POST',
            $header,
            $data
        );

        $this->curl->close();

        /* decode and parse */
        $result = json_decode($result, true);

        if (empty($result)) {
            return true;
        }

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->logger->error(sprintf('Invalid response (json decode failed)'));
            return false;
        }

        if (array_key_exists('code', $result) && $result['code'] != 201) {
            $message = array_key_exists('message', $result) ? $result['message'] : '';
            $this->logger->error(sprintf('Creating an alert login fail for Integration %s got error, code: %d, message: %s',
                $integrationCName,
                $result['code'],
                $message
            ));
            return false;
        }

        $this->logger->info('finished created alert login fail');

        return true;
    }

    /**
     * @inheritdoc
     */
    public function createAlertWhenTimeOut($publisherId, $integrationCName, $dataSourceId, DateTime $startDate, DateTime $endDate, $executionDate)
    {
        $this->logger->info(sprintf('Creating an alert time out for Integration %s', $integrationCName));

        $header = array('Authorization: Bearer ' . $this->getToken());

        $data = [
            'code' => 2002,
            'detail' => [
                'integrationName' => $integrationCName,
                'integrationCName' => $integrationCName,
                PartnerParams::PARAM_KEY_DATA_SOURCE_ID => $dataSourceId,
                PartnerParams::PARAM_KEY_START_DATE => $startDate->format('Y-m-d'),
                PartnerParams::PARAM_KEY_END_DATE => $endDate->format('Y-m-d'),
                'executionDate' => $executionDate
            ],
            PartnerParams::PARAM_KEY_DATA_SOURCE => $dataSourceId,
            PartnerParams::PARAM_KEY_PUBLISHER => $publisherId
        ];

        $result = $this->curl->executeQuery(
            $this->urCreateAlertUrl,
            'POST',
            $header,
            $data
        );

        $this->curl->close();

        /* decode and parse */
        $result = json_decode($result, true);

        if (empty($result)) {
            return true;
        }

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->logger->error(sprintf('Invalid response (json decode failed)'));
            return false;
        }

        if (array_key_exists('code', $result) && $result['code'] != 201) {
            $message = array_key_exists('message', $result) ? $result['message'] : '';
            $this->logger->error(sprintf('Creating an alert time out  for Integration %s got error, code: %d, message: %s',
                $integrationCName,
                $result['code'],
                $message
            ));
            return false;
        }

        $this->logger->info('finished created alert time out');

        return true;
    }

    /**
     * @inheritdoc
     */
    public function updateIntegrationWhenDownloadSuccess($partnerParams)
    {
        if (!$partnerParams instanceof PartnerParamInterface) {
            return;
        }

        $scheduleId = $partnerParams->getDataSourceIntegrationScheduleId();
        $dataSourceIntegrationBackFillHistoryId = $partnerParams->getDataSourceIntegrationBackFillHistoryId();

        if ($partnerParams->getBackFill()) {
            /** Add try catch to prevent exception make fetcher fail */

            try {
                if (!empty($dataSourceIntegrationBackFillHistoryId)) {
                    $this->updateBackFillHistory($dataSourceIntegrationBackFillHistoryId, $pending = false, $executeAt = date_create()->format('Y-m-d H:i:s'));
                }
            } catch (\Exception $e) {
                $this->logger->warning(sprintf('Update back fill fail data source integration id %s', $scheduleId));
            }
        } else {
            /** Add try catch to prevent exception make fetcher fail */
            try {
                if (!empty($scheduleId)) {
                    $this->updateIntegrationSchedule($scheduleId, $pending = false);
                    $this->updateExecuteAtForIntegrationSchedule($scheduleId);
                }
            } catch (\Exception $e) {
                $this->logger->warning(sprintf('Update last executed fail for schedule id %s', $scheduleId));
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function updateIntegrationWhenRunFail($partnerParams)
    {
        if (!$partnerParams instanceof PartnerParamInterface) {
            return;
        }

        $scheduleId = $partnerParams->getDataSourceIntegrationScheduleId();
        $dataSourceIntegrationBackFillHistoryId = $partnerParams->getDataSourceIntegrationBackFillHistoryId();

        if ($partnerParams->getBackFill()) {
            /** Add try catch to prevent exception make fetcher fail */

            try {
                if (!empty($dataSourceIntegrationBackFillHistoryId)) {
                    $this->updateBackFillHistory($dataSourceIntegrationBackFillHistoryId, $pending = false, $executedAt = null);
                }
            } catch (\Exception $e) {
                $this->logger->warning(sprintf('Update pending for back fill history fail id %s', $scheduleId));
            }
        } else {
            /** Add try catch to prevent exception make fetcher fail */
            try {
                if (!empty($scheduleId)) {
                    $this->updateIntegrationSchedule($scheduleId, $pending = false);
                }
            } catch (\Exception $e) {
                $this->logger->warning(sprintf('Update pending fail for schedule id %s', $scheduleId));
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function updateExecuteAtForIntegrationSchedule($dataSourceIntegrationScheduleId)
    {
        $this->logger->info(sprintf('Updating last execution time'));

        /* get token */
        $header = array('Authorization: Bearer ' . $this->getToken());

        /* post update to ur api */
        $data = [
        ];

        $url = preg_replace('{{id}}', $dataSourceIntegrationScheduleId, $this->updateNextExecuteAtForScheduleUrl);

        $result = $this->curl->executeQuery(
            $url,
            'POST',
            $header,
            $data
        );

        $this->curl->close();

        /* decode and parse */
        $result = json_decode($result, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->logger->error(sprintf('Invalid response (json decode failed)'));
            return false;
        }

        if (array_key_exists('code', $result) && $result['code'] != 200) {
            $this->logger->error(sprintf('Update last execution time failed, code %d', $result['code']));
            return false;
        }

        return (bool)$result;
    }

    /**
     * @inheritdoc
     */
    public function updateBackFillHistory($dataSourceIntegrationBackFillHistoryId, $pending = false, $lastExecutedAt = null)
    {
        $this->logger->info(sprintf('Updating pending for backFill history'));

        if ($lastExecutedAt instanceof DateTime) {
            $lastExecutedAt = $lastExecutedAt->format('Y-m-d H:i:s');
        }

        /* get token */
        $header = array('Authorization: Bearer ' . $this->getToken());

        /* post update to ur api */
        $data = [
            self::PENDING => $pending,
            self::EXECUTED_AT => $lastExecutedAt
        ];

        $url = preg_replace('{{id}}', $dataSourceIntegrationBackFillHistoryId, $this->updateBackFillHistoryUrl);

        $result = $this->curl->executeQuery(
            $url,
            'POST',
            $header,
            $data
        );

        $this->curl->close();

        /* decode and parse */
        $result = json_decode($result, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->logger->error(sprintf('Invalid response (json decode failed)'));
            return false;
        }

        if (array_key_exists('code', $result) && $result['code'] != 200) {
            $this->logger->error(sprintf('Updating pending for backFill history failed, code %d', $result['code']));
            return false;
        }

        return (bool)$result;
    }

    /**
     * @inheritdoc
     */
    public function updateIntegrationSchedule($dataSourceIntegrationScheduleId, $pending = false)
    {
        $this->logger->info(sprintf('Updating pending for schedule'));

        /* get token */
        $header = array('Authorization: Bearer ' . $this->getToken());

        /* post update to ur api */
        $data = [
            self::PENDING => $pending
        ];

        $url = preg_replace('{{id}}', $dataSourceIntegrationScheduleId, $this->updateScheduleUrl);

        $result = $this->curl->executeQuery(
            $url,
            'POST',
            $header,
            $data
        );

        $this->curl->close();

        /* decode and parse */
        $result = json_decode($result, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->logger->error(sprintf('Invalid response (json decode failed)'));
            return false;
        }

        if (array_key_exists('code', $result) && $result['code'] != 200) {
            $this->logger->error(sprintf('Updating pending for schedule failed, code %d', $result['code']));
            return false;
        }

        return (bool)$result;
    }


    /**
     * @inheritdoc
     */
    public function createAlertWhenAppearUpdatePassword($publisherId, $integrationCName, $dataSourceId, $message, DateTime $executionDate, $username, $url)
    {
        $this->logger->info(sprintf('Creating an alert login fail for Integration %s', $integrationCName));

        $header = array('Authorization: Bearer ' . $this->getToken());

        $data = [
            'code' => 2003,
            'detail' => [
                'integrationCName' => $integrationCName,
                PartnerParams::PARAM_KEY_DATA_SOURCE_ID => $dataSourceId,
                'message' => $message,
                'executionDate' => $executionDate->format('Y-m-d'),
                'username' => $username,
                'url' => $url
            ],
            PartnerParams::PARAM_KEY_DATA_SOURCE => $dataSourceId,
            PartnerParams::PARAM_KEY_PUBLISHER => $publisherId
        ];

        $result = $this->curl->executeQuery(
            $this->urCreateAlertUrl,
            'POST',
            $header,
            $data
        );

        $this->curl->close();

        /* decode and parse */
        $result = json_decode($result, true);

        if (empty($result)) {
            return true;
        }

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->logger->error(sprintf('Invalid response (json decode failed)'));
            return false;
        }

        if (array_key_exists('code', $result) && $result['code'] != 201) {
            $message = array_key_exists('message', $result) ? $result['message'] : '';
            $this->logger->error(sprintf('Creating an alert login fail for Integration %s got error, code: %d, message: %s',
                $integrationCName,
                $result['code'],
                $message
            ));
            return false;
        }

        $this->logger->info('finished created alert Password expiry');

        return true;
    }
}