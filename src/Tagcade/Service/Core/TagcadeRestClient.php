<?php

namespace Tagcade\Service\Core;

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
    private $updateLastExecutionTimeForIntegrationUrl;

    /** @var string */
    private $token;

    /** @var LoggerInterface */
    private $logger;

    function __construct(CurlRestClient $curl, $username, $password,
                         $getTokenUrl,
                         $getListPublisherUrl,
                         $getListIntegrationsToBeExecutedUrl,
                         $updateLastExecutionTimeForIntegrationUrl
    )
    {
        $this->curl = $curl;
        $this->username = $username;
        $this->password = $password;

        $this->getTokenUrl = $getTokenUrl;
        $this->getListPublisherUrl = $getListPublisherUrl;
        $this->getListIntegrationsToBeExecutedUrl = $getListIntegrationsToBeExecutedUrl;
        $this->updateLastExecutionTimeForIntegrationUrl = $updateLastExecutionTimeForIntegrationUrl;
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
    public function getDataSourceIntegrationToBeExecuted()
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
                || !array_key_exists('dataSource', $dataSourceIntegration)
                || !array_key_exists('integration', $dataSourceIntegration)
                || !array_key_exists('params', $dataSourceIntegration)
            ) {
                return false;
            }

            return true;
        });

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function updateLastExecutionTimeForIntegrationByCName($dataSourceIntegrationId, \DateTime $dateTime)
    {
        $this->logger->info(sprintf('Getting all Integrations to be executed'));

        /* get token */
        $header = array('Authorization: Bearer ' . $this->getToken());

        /* post update to ur api */
        $data = [
            'id' => $dataSourceIntegrationId,
            'lastExecutedAt' => $dateTime,
        ];

        $publishers = $this->curl->executeQuery(
            $this->updateLastExecutionTimeForIntegrationUrl,
            'POST',
            $header,
            $data
        );

        $this->curl->close();

        /* decode and parse */
        $result = json_decode($publishers, true);

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
}