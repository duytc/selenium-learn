<?php

namespace Tagcade\Service;


use RestClient\CurlRestClient;

class TagcadeApiService implements TagcadeApiServiceInterface
{
    /**
     * @inheritdoc
     */
    public function getToken($tokenUrl, $username, $password)
    {
        $curl = new CurlRestClient();
        $data = array('username' => $username, 'password' => $password);

        $tokenResponse = $curl->executeQuery($tokenUrl, 'POST', array(), $data);
        $curl->close();
        $token = json_decode($tokenResponse, true);

        if (empty($token)) {
            return false;
        }

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('json decoding for token error');
        }

        if (!array_key_exists('token', $token)) {
            throw new \Exception(sprintf('Could not authenticate user %s', $username));
        }
        $token = $token['token'];

        return $token;
    }

    /**
     * @inheritdoc
     */
    public function getReports($url, $method = 'GET', $header = null, $data = array())
    {
        $curl = new CurlRestClient();
        $responseData = $curl->executeQuery($url, $method, $header, $data);
        $curl->close();

        return $responseData;
    }
}