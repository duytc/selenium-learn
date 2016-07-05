<?php

namespace Tagcade\Service\Core;

use Psr\Log\LoggerInterface;
use RestClient\CurlRestClient;

class TagcadeRestClient implements TagcadeRestClientInterface
{
    /**
     * @var null
     */
    private $username;
    /**
     * @var array
     */
    private $password;
    /**
     * @var CurlRestClient
     */
    private $curl;
    /**
     * @var string
     */
    private $getTokenUrl;
    /**
     * @var string
     */
    private $getListPublisherUrl;
    private $token;
    /**
     * @var LoggerInterface
     */
    private $logger;

    const DEBUG = 0;

    function __construct(CurlRestClient $curl, $username, $password, $getTokenUrl, $getListPublisherUrl)
    {
        $this->username = $username;
        $this->password = $password;
        $this->curl = $curl;
        $this->getTokenUrl = $getTokenUrl;
        $this->getListPublisherUrl = $getListPublisherUrl;
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

        $data = array('username' => $this->username, 'password'  => $this->password);
        $tokenResponse = $this->curl->executeQuery($this->getTokenUrl, 'POST', array(),  $data);
        $this->curl->close();
        $token = json_decode($tokenResponse, true);

        if (empty($token)) {
            $this->logger->error(sprintf('Cannot get token with returned message: %s', $tokenResponse));

            return null;
        }

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('json decoding for token error');
        }
        if (!array_key_exists('token', $token) ) {
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
}