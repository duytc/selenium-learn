<?php

namespace Tagcade\Service\Core;

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
    const DEBUG = 0;

    function __construct(CurlRestClient $curl, $username, $password, $getTokenUrl, $getListPublisherUrl)
    {
        $this->username = $username;
        $this->password = $password;
        $this->curl = $curl;
        $this->getTokenUrl = $getTokenUrl;
        $this->getListPublisherUrl = $getListPublisherUrl;
    }

    public function getToken($force = false)
    {
        if ($this->token != null && $force == false) {
            return $this->token;
        }

        $data = array('username' => $this->username, 'password'  => $this->password);
        $token = $this->curl->executeQuery($this->getTokenUrl, 'POST', array(),  $data);
        $this->curl->close();
        $token = json_decode($token, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('json decoding for token error');
        }
        if (!array_key_exists('token', $token) ) {
            throw new \Exception(sprintf('Could not authenticate user %s', $this->username));
        }

        $this->token = $token['token'];

        return $this->token;
    }

    public function getPartnerConfigurationForAllPublishers($partnerCName)
    {
        $header = array('Authorization: Bearer ' . $this->getToken());

        $publishers = $this->curl->executeQuery(
            str_replace('{cname}', $partnerCName, $this->getListPublisherUrl),
            'GET',
            $header
        );

        $this->curl->close();
        return json_decode($publishers, true);
    }
}