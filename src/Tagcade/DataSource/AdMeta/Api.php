<?php

namespace Tagcade\DataSource\AdMeta;

use anlutro\cURL\cURL;
use DateTime;
use Psr\Log\LoggerInterface;
use Tagcade\DataSource\AdMeta\Exception\BadResponseException;

class Api
{
    const BASE_API_URL = 'https://tango.admeta.com/api';
    const DATE_FORMAT = 'Y-m-d';

    /**
     * @var string
     */
    private $username;
    /**
     * @var string
     */
    private $password;
    /**
     * @var cURL
     */
    private $curl;
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param string $username
     * @param string $password
     * @param cURL $curl
     * @param LoggerInterface $logger
     */
    public function __construct($username, $password, cUrl $curl, LoggerInterface $logger = null)
    {
        $this->username = $username;
        $this->password = $password;
        $this->curl = $curl;
        $this->logger = $logger;
    }

    public function getAdvertisers()
    {
        return $this->doGet($this->getLiveApiUrl('advertisers'));
    }

    public function getAgencies()
    {
        return $this->doGet($this->getLiveApiUrl('agencies'));
    }

    public function getSubPublishers()
    {
        $query = [
            'name' => '',
            'zipcode' => '',
            'street' => '',
            'city' => '',
            'orderBy' => '',
        ];

        $url = $this->curl->buildUrl(
            $this->getLiveApiUrl('subpublishers'),
            $query
        );

        return $this->doGet($url);
    }

    public function getUsers()
    {
        $query = [
            'name' => '',
            'email' => '',
            'phone' => '',
            'mobile' => '',
            'orderBy' => '',
        ];

        $url = $this->curl->buildUrl(
            $this->getLiveApiUrl('users'),
            $query
        );

        return $this->doGet($url);
    }

    public function getSites()
    {
        $query = [
            'count' => '',
            'offset' => '',
            'limit' => '',
        ];

        $url = $this->curl->buildUrl(
            $this->getLiveApiUrl('sites'),
            $query
        );

        return $this->doGet($url);
    }

    public function getPlacements()
    {
        $query = [
            'count' => '',
            'offset' => '',
            'limit' => '',
        ];

        $url = $this->curl->buildUrl(
            $this->getLiveApiUrl('sites/placements'),
            $query
        );

        return $this->doGet($url);
    }

    /**
     * @param DateTime $date
     * @return string
     * @throws BadResponseException
     */
    public function getReports(DateTime $date = null)
    {
        if (!$date) {
            $date = new DateTime('yesterday');
        }

        $query = [
            'date-from' => $date->format(static::DATE_FORMAT),
            'date-to' => $date->format(static::DATE_FORMAT),
            'limit' => '1000',
            'offset' => '1000',
            'group-by' => 'date,website,webpage,placement,order',
            'detailed-tracking-info' => '1',
            'detailed-revenue-info' => 1,
            'custom-fields' => '1'
        ];

        $url = $this->curl->buildUrl(
            $this->getReportApiUrl(),
            $query
        );

        if ($this->logger) {
            $this->logger->info('Start fetching report data');
        }

        $data = $this->doGet($url);

        if ($this->logger) {
            $this->logger->info('Finished fetching report data');
        }

        return $data;
    }

    /**
     * @param string $url
     * @throws BadResponseException
     * @return string
     */
    protected function doGet($url)
    {
        $request = $this->curl->newRequest('get', $url)
            ->auth($this->username, $this->password)
        ;

        $response = $request->send();

        $data = $response->body;
        $statusCode = $response->statusCode;

        if (!$this->okStatus($statusCode)) {
            throw new BadResponseException($data, $statusCode);
        }

        $data = $this->addXmlDeclarationIfMissing($data);

        return $data;
    }

    /**
     * @param int $statusCode
     * @return bool
     */
    protected function okStatus($statusCode)
    {
        return $statusCode >= 200 && $statusCode < 300;
    }

    /**
     * @param string $resource
     * @return string
     */
    protected function getLiveApiUrl($resource)
    {
        return sprintf('%s/CRMHTTPService/CRMHTTPService.svc/%s', static::BASE_API_URL, $resource);
    }

    /**
     * @param string [$resource]
     * @return string
     */
    protected function getReportApiUrl($resource = null)
    {
        $url = sprintf('%s/ReportsHTTPService/ReportsHTTPService.svc/reports', static::BASE_API_URL);

        if ($resource) {
            $url .= sprintf('/%s', $resource);
        }

        return $url;
    }

    /**
     * @param $data
     * @return string
     */
    protected function addXmlDeclarationIfMissing($data)
    {
        if (strpos(ltrim($data), '<?xml') === 0) {
            return $data;
        }

        return '<?xml version="1.0" encoding="utf-8"?>' . $data;
    }
}