<?php

namespace Tagcade\Service\Core;

use DateTime;
use Psr\Log\LoggerInterface;
use RestClient\CurlRestClient;

class TagcadeRestClient implements TagcadeRestClientInterface
{
    const DEBUG = 0;

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
    private $updateNextExecuteAtForDataSourceIntegrationScheduleUrl;

    /** @var string */
    private $updateBackFillExecutedForDataSourceIntegrationScheduleUrl;

    /** @var string */
    private $urCreateAlertUrl;

    /** @var string */
    private $token;

    /** @var LoggerInterface */
    private $logger;

    function __construct(CurlRestClient $curl, $username, $password,
                         $getTokenUrl,
                         $getListPublisherUrl,
                         $getListIntegrationsToBeExecutedUrl,
                         $getListIntegrationsByDataSourceIdUrl,
                         $updateNextExecuteAtForDataSourceIntegrationScheduleUrl,
                         $updateBackFillExecutedForDataSourceIntegrationScheduleUrl,
                         $urCreateAlertUrl
    )
    {
        $this->curl = $curl;
        $this->username = $username;
        $this->password = $password;

        $this->getTokenUrl = $getTokenUrl;
        $this->getListPublisherUrl = $getListPublisherUrl;
        $this->getListIntegrationsToBeExecutedUrl = $getListIntegrationsToBeExecutedUrl;
        $this->getListIntegrationsByDataSourceIdUrl = $getListIntegrationsByDataSourceIdUrl;
        $this->updateNextExecuteAtForDataSourceIntegrationScheduleUrl = $updateNextExecuteAtForDataSourceIntegrationScheduleUrl;
        $this->updateBackFillExecutedForDataSourceIntegrationScheduleUrl = $updateBackFillExecutedForDataSourceIntegrationScheduleUrl;
        $this->urCreateAlertUrl = $urCreateAlertUrl;
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
            throw new \Exception('json decoding for token error');
        }
        if (!array_key_exists('token', $token)) {
            throw new \Exception(sprintf('Could not authenticate user %s', $this->username));
        }

        $this->token = $token['token'];

        $this->logger->info(sprintf('Got token %s', $this->token));

        return $this->token;
    }

    public function getPartnerConfigurationForAllPublishers($partnerCName, $publisherId = null)
    {
        $this->logger->info(sprintf('Getting publisher configuration for partner %s', $partnerCName));

        $header = array('Authorization: Bearer ' . $this->getToken());

        $data = is_numeric($publisherId) ? ['publisher' => $publisherId] : [];
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

        /* filter invalid integrations */
        $result = array_filter($result, function ($dataSourceIntegration) {
            if (!is_array($dataSourceIntegration)
                || !array_key_exists('id', $dataSourceIntegration)
                || !array_key_exists('dataSourceIntegration', $dataSourceIntegration)
            ) {
                return false;
            }

            return true;
        });

        if ($dataSourceId) {
            $result = array_filter($result, function ($dataSourceIntegrationSchedule) use ($dataSourceId) {
                return $dataSourceId = $dataSourceIntegrationSchedule['dataSourceIntegration']['dataSource']['id'] == $dataSourceId;
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
            'datasource' => $dataSourceId
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

        /* filter invalid integrations to make sure correct dataSourceId again */
        $result = array_filter($result, function ($dataSourceIntegrationSchedule) use ($dataSourceId) {
            if (!array_key_exists('dataSourceIntegration', $dataSourceIntegrationSchedule)) {
                return false;
            }

            $dataSourceIntegration = $dataSourceIntegrationSchedule['dataSourceIntegration'];

            if (!array_key_exists('dataSource', $dataSourceIntegration)
                || !array_key_exists('id', $dataSourceIntegration['dataSource'])
            ) {
                return false;
            }

            if ($dataSourceIntegration['dataSource']['id'] != $dataSourceId) {
                return false;
            }

            return true;
        });

        $this->logger->info(sprintf('Found %d Integrations to be executed', count($result)));

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function updateNextExecuteAtForIntegrationSchedule($dataSourceIntegrationScheduleId)
    {
        $this->logger->info(sprintf('Updating last execution time'));

        /* get token */
        $header = array('Authorization: Bearer ' . $this->getToken());

        /* post update to ur api */
        $data = [
            'id' => $dataSourceIntegrationScheduleId,
        ];

        $result = $this->curl->executeQuery(
            $this->updateNextExecuteAtForDataSourceIntegrationScheduleUrl,
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
    public function updateBackFillExecutedForIntegration($dataSourceIntegrationScheduleId)
    {
        $this->logger->info(sprintf('Updating backfill executed'));

        /* get token */
        $header = array('Authorization: Bearer ' . $this->getToken());

        /* post update to ur api */
        $data = [
            'id' => $dataSourceIntegrationScheduleId,
        ];

        $url = $this->updateBackFillExecutedForDataSourceIntegrationScheduleUrl;
        //$url = $this->updateBackFillExecutedForDataSourceIntegrationScheduleUrl . '?XDEBUG_SESSION_START=1'; // for debug only

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
            $this->logger->error(sprintf('Update backfill executed failed, code %d', $result['code']));
            return false;
        }

        return (bool)$result;
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
                'dataSourceId' => $dataSourceId,
                'startDate' => $startDate->format('Y-m-d'),
                'endDate' => $endDate->format('Y-m-d'),
                'executionDate' => $executionDate->format('Y-m-d')
            ],
            'publisher' => $publisherId
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
}