<?php

namespace Tagcade\Service;

interface TagcadeApiServiceInterface
{
    /**
     * Get token in tagcade api
     * @param $tokenUrl
     * @param $username
     * @param $password
     * @return mixed
     */
    public function getToken($tokenUrl, $username, $password);

    /**
     * Get reports of tagcade api
     * @param $url
     * @param string $method
     * @param null $header
     * @param array $data
     * @return mixed
     */
    public function getReports($url, $method = 'GET', $header = null, $data = array());
}