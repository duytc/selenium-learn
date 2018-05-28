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

    const FIELD_QUEUED_AT = 'queuedAt';
    const FIELD_NEXT_EXECUTED_AT = 'nextExecutionAt';
    const FIELD_FINISH_AT = 'finishedAt';
    const FIELD_STATUS = 'status';
    const FIELD_UUID = 'uuid';

    /** const type alert */
    const ALERT_TYPE = 'type';
    const ALERT_TYPE_INFO = 'info';
    const ALERT_TYPE_WARNING = 'warning';
    const ALERT_TYPE_ERROR = 'error';

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
    private $urCreateAlertUrl;

    /** @var string */
    private $token;

    /** @var LoggerInterface */
    private $logger;

    /** @var string */
    private $updateSchedulePendingUrl;
    /** @var string */
    private $updateScheduleFinishOrFailUrl;
    /** @var string */
    private $updateBackFillHistoryUrl;
    /** @var string */
    private $updateBackfillMissingDatesUrl;

    /** @var string */
    private $getListIntegrationsByIntegrationIdUrl;

    function __construct(CurlRestClient $curl, $username, $password,
                         $getTokenUrl,
                         $getListPublisherUrl,
                         $getListIntegrationsToBeExecutedUrl,
                         $getListIntegrationsByDataSourceIdUrl,
                         $urCreateAlertUrl,
                         $updateSchedulePendingUrl,
                         $updateScheduleFinishOrFailUrl,
                         $updateBackFillHistoryUrl,
                         $updateBackfillMissingDatesUrl,
                         $getListIntegrationsByIntegrationIdUrl
    )
    {
        $this->curl = $curl;
        $this->username = $username;
        $this->password = $password;

        $this->getTokenUrl = $getTokenUrl;
        $this->getListPublisherUrl = $getListPublisherUrl;
        $this->getListIntegrationsToBeExecutedUrl = $getListIntegrationsToBeExecutedUrl;
        $this->getListIntegrationsByDataSourceIdUrl = $getListIntegrationsByDataSourceIdUrl;
        $this->urCreateAlertUrl = $urCreateAlertUrl;
        $this->updateSchedulePendingUrl = $updateSchedulePendingUrl;
        $this->updateScheduleFinishOrFailUrl = $updateScheduleFinishOrFailUrl;
        $this->updateBackFillHistoryUrl = $updateBackFillHistoryUrl;
        $this->updateBackfillMissingDatesUrl = $updateBackfillMissingDatesUrl;
        $this->getListIntegrationsByIntegrationIdUrl = $getListIntegrationsByIntegrationIdUrl;
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
            $this->logger->warning(sprintf('Cannot get token with returned message: %s', $tokenResponse));

            return null;
        }

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('json decoding for token error');
        }
        if (!array_key_exists('token', $token)) {
            $this->logger->warning(sprintf('Could not authenticate user %s', $this->username));
            throw new Exception();
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
            $this->logger->notice(sprintf('Invalid response (json decode failed)'));
            return false;
        }

        if (array_key_exists('code', $result) && $result['code'] != 200) {
            $this->logger->info(sprintf('Not found Integration to be executed'));
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
            $this->logger->notice(sprintf('Invalid response (json decode failed)'));
            return false;
        }

        if (array_key_exists('code', $result) && $result['code'] != 200) {
            $this->logger->info(sprintf('Not found Integration to be executed'));
            return false;
        }

        $this->logger->info(sprintf('Found %d Integrations to be executed', count($result)));

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function getDataSourceIntegrationSchedulesByIntegration($integrationId)
    {
        $this->logger->info(sprintf('Getting all Integrations Schedule to be executed'));

        /* get token */
        $header = array('Authorization: Bearer ' . $this->getToken());

        /* get from ur api */
        $data = [
            PartnerParams::PARAM_KEY_INTEGRATION => $integrationId
        ];
        $url = $this->getListIntegrationsByIntegrationIdUrl;
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
            $this->logger->notice(sprintf('Invalid response (json decode failed)'));
            return false;
        }

        if (array_key_exists('code', $result) && $result['code'] != 200) {
            $this->logger->info(sprintf('Not found Integration to be executed'));
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
        $this->logger->warning(sprintf('Creating an alert login fail for Integration %s', $integrationCName));

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
            PartnerParams::PARAM_KEY_PUBLISHER => $publisherId,
            self::ALERT_TYPE => self::ALERT_TYPE_ERROR
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
            $this->logger->notice(sprintf('Invalid response (json decode failed)'));
            return false;
        }

        if (array_key_exists('code', $result) && $result['code'] != 201) {
            $message = array_key_exists('message', $result) ? $result['message'] : '';
            $this->logger->notice(sprintf('Creating an alert login fail for Integration %s got error, code: %d, message: %s',
                $integrationCName,
                $result['code'],
                $message
            ));
            return false;
        }

        $this->logger->warning('Finished created alert login fail.');

        return true;
    }

    /**
     * @inheritdoc
     */
    public function createAlertWhenTimeOut($publisherId, $integrationCName, $dataSourceId, DateTime $startDate, DateTime $endDate, $executionDate)
    {
        $this->logger->notice(sprintf('Creating an alert time out for Integration %s', $integrationCName));

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
            PartnerParams::PARAM_KEY_PUBLISHER => $publisherId,
            self::ALERT_TYPE => self::ALERT_TYPE_ERROR
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
            $this->logger->notice(sprintf('Invalid response (json decode failed)'));
            return false;
        }

        if (array_key_exists('code', $result) && $result['code'] != 201) {
            $message = array_key_exists('message', $result) ? $result['message'] : '';
            $this->logger->notice(sprintf('Creating an alert time out  for Integration %s got error, code: %d, message: %s',
                $integrationCName,
                $result['code'],
                $message
            ));
            return false;
        }

        $this->logger->notice('finished created alert time out');

        return true;
    }

    /**
     * @inheritdoc
     */
    public function createAlertWhenAppearUpdatePassword($publisherId, $integrationCName, $dataSourceId, $message, DateTime $executionDate, $username, $url)
    {
        $this->logger->notice(sprintf('Creating an alert remind the customer update password for Integration %s', $integrationCName));

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
            PartnerParams::PARAM_KEY_PUBLISHER => $publisherId,
            self::ALERT_TYPE => self::ALERT_TYPE_WARNING
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
            $this->logger->notice(sprintf('Invalid response (json decode failed)'));
            return false;
        }

        if (array_key_exists('code', $result) && $result['code'] != 201) {
            $message = array_key_exists('message', $result) ? $result['message'] : '';
            $this->logger->notice(sprintf('Creating an alert remind the customer update password for Integration %s got error, code: %d, message: %s',
                $integrationCName,
                $result['code'],
                $message
            ));
            return false;
        }

        $this->logger->warning('finished created alert Password expiry');

        return true;
    }

    /**
     * @inheritdoc
     */
    public function updateIntegrationWhenDownloadSuccess(PartnerParamInterface $partnerParams)
    {
        $scheduleId = $partnerParams->getDataSourceIntegrationScheduleId();
        $scheduleUUID = $partnerParams->getDataSourceIntegrationScheduleUUID();
        $dataSourceIntegrationBackFillHistoryId = $partnerParams->getDataSourceIntegrationBackFillHistoryId();

        if ($partnerParams->getBackFill()) {
            /** Add try catch to prevent exception make fetcher fail */

            try {
                if (!empty($dataSourceIntegrationBackFillHistoryId)) {
                    $this->updateBackFillHistory($dataSourceIntegrationBackFillHistoryId, $status = TagcadeRestClient::FETCHER_STATUS_FINISHED);

                    // check all backfill with this dataSourceIntegration if all return autoCreate false => change backfillMissingDates to false
                    $this->updateBackFillMissingDates($partnerParams->getDataSourceIntegrationId(), $partnerParams->getDataSourceId());
                }
            } catch (\Exception $e) {
                $this->logger->notice(sprintf('Update back fill fail data source integration id %s', $scheduleId));
            }
        } else {
            /** Add try catch to prevent exception make fetcher fail */
            try {
                if (!empty($scheduleUUID)) {
                    if (!$partnerParams->getFetcherActivatorDataSourceForce()) {
                        $this->updateIntegrationScheduleFinishOrFail($scheduleUUID, $status = TagcadeRestClient::FETCHER_STATUS_FINISHED);
                    }
                }
            } catch (\Exception $e) {
                $this->logger->notice(sprintf('Update last executed fail for schedule id %s and uuid %s', $scheduleId, $scheduleUUID));
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function updateIntegrationWhenRunFail(PartnerParamInterface $partnerParams)
    {
        $scheduleId = $partnerParams->getDataSourceIntegrationScheduleId();
        $scheduleUUID = $partnerParams->getDataSourceIntegrationScheduleUUID();
        $dataSourceIntegrationBackFillHistoryId = $partnerParams->getDataSourceIntegrationBackFillHistoryId();

        if ($partnerParams->getBackFill()) {
            /** Add try catch to prevent exception make fetcher fail */

            try {
                if (!empty($dataSourceIntegrationBackFillHistoryId)) {
                    $this->updateBackFillHistory($dataSourceIntegrationBackFillHistoryId, $status = TagcadeRestClient::FETCHER_STATUS_FAILED);
                    // check all backfill with this dataSourceIntegration if all return autoCreate false => change backfillMissingDates to false
                    $this->updateBackFillMissingDates($partnerParams->getDataSourceIntegrationId(), $partnerParams->getDataSourceId());
                }
            } catch (\Exception $e) {
                $this->logger->notice(sprintf('Update status for back fill history fail id %s', $scheduleId));
            }
        } else {
            /** Add try catch to prevent exception make fetcher fail */
            try {
                if (!empty($scheduleId)) {
                    if (!$partnerParams->getFetcherActivatorDataSourceForce()) {
                        $this->updateIntegrationScheduleFinishOrFail($scheduleUUID, $status = TagcadeRestClient::FETCHER_STATUS_FAILED);
                    }
                }
            } catch (\Exception $e) {
                $this->logger->notice(sprintf('Update status fail for schedule id %s and uuid %s', $scheduleId, $scheduleUUID));
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function updateIntegrationSchedule($dataSourceIntegrationScheduleUUID, $status = self::FETCHER_STATUS_PENDING)
    {
        $queuedAt = null;
        $this->logger->info(sprintf('Updating status is pending for schedule'));
        /* get token */
        $header = array('Authorization: Bearer ' . $this->getToken());

        /* post update to ur api */
        $data = [
            self::FIELD_UUID => $dataSourceIntegrationScheduleUUID,
            self::FIELD_STATUS => $status,
        ];

        $url = $this->updateSchedulePendingUrl;

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
            $this->logger->notice(sprintf('Invalid response (json decode failed)'));
            return false;
        }

        if (array_key_exists('code', $result) && $result['code'] != 200) {
            $this->logger->notice(sprintf('Updating status is pending for schedule failed, code %d. Message: %s', $result['code'], $result['message']));
            return false;
        }

        return (bool)$result;
    }

    /**
     * @inheritdoc
     */
    public function updateBackFillHistory($dataSourceIntegrationBackFillHistoryId, $status, $queuedAt = null, $finishedAt = null)
    {
        $this->logger->info(sprintf('Updating status for backFill history'));

        /* get token */
        $header = array('Authorization: Bearer ' . $this->getToken());

        /* post update to ur api */
        $data = [
            self::FIELD_STATUS => $status,
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
            $this->logger->notice(sprintf('Invalid response (json decode failed)'));
            return false;
        }

        if (array_key_exists('code', $result) && $result['code'] != 200) {
            $this->logger->notice(sprintf('Updating status for backFill history failed, code %d', $result['code']));
            return false;
        }

        return (bool)$result;
    }

    /**
     * @inheritdoc
     */
    public function updateIntegrationScheduleFinishOrFail($dataSourceIntegrationScheduleUUID, $status, $nextExecutionAt = null, $finishedAt = null)
    {
        $this->logger->info(sprintf('Updating status is %d for schedule', $status));
        /* get token */
        $header = array('Authorization: Bearer ' . $this->getToken());

        /* post update to ur api */
        $data = [
            self::FIELD_STATUS => $status,
            self::FIELD_UUID => $dataSourceIntegrationScheduleUUID,
        ];

        $url = $this->updateScheduleFinishOrFailUrl;

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
            $this->logger->notice(sprintf('Invalid response (json decode failed)'));
            return false;
        }

        if (array_key_exists('code', $result) && $result['code'] != 200) {
            $this->logger->notice(sprintf('Updating status is %d for schedule failed, code %d. Message: %s', $status, $result['code'], $result['message']));
            return false;
        }

        return (bool)$result;
    }

    /**
     * @inheritdoc
     */
    public function updateBackFillMissingDates($dataSourceIntegrationId, $dataSourceId)
    {
        $this->logger->info('Checking autoCreate and then update backfillMissingDate');
        /* get token */
        $header = array('Authorization: Bearer ' . $this->getToken());

        /* post update to ur api */
        $data = [
            PartnerParams::PARAM_KEY_DATA_SOURCE => $dataSourceId,
            PartnerParams::PARAM_KEY_DATA_SOURCE_INTEGRATION => $dataSourceIntegrationId,
        ];

        $url = $this->updateBackfillMissingDatesUrl;
        $url = preg_replace('{{id}}', $dataSourceId, $url);
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
            $this->logger->notice(sprintf('Invalid response (json decode failed)'));
            return false;
        }

        if (array_key_exists('code', $result) && $result['code'] != 200) {
            $this->logger->notice('Updating backfillMissingDatesRunning failed');
            return false;
        }

        return (bool)$result;

    }
}