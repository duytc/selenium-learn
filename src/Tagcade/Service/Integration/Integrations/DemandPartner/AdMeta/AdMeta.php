<?php

namespace Tagcade\Service\Integration\Integrations\DemandPartner\AdMeta;

use anlutro\cURL\cURL;
use anlutro\cURL\Request;
use DateTime;
use Psr\Log\LoggerInterface;
use Tagcade\Service\FileStorageServiceInterface;
use Tagcade\Service\Integration\Config;
use Tagcade\Service\Integration\ConfigInterface;
use Tagcade\Service\Integration\Integrations\DemandPartner\AdMeta\Exception\BadResponseException;
use Tagcade\Service\Integration\Integrations\IntegrationAbstract;
use Tagcade\Service\Integration\Integrations\IntegrationInterface;

class AdMeta extends IntegrationAbstract implements IntegrationInterface
{
    const INTEGRATION_C_NAME = 'admeta';

    const BASE_API_URL = 'https://tango.admeta.com/api';
    const DATE_FORMAT = 'Y-m-d';

    /**
     * @var cUrl
     */
    protected $curl;
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var FileStorageServiceInterface
     */
    protected $fileStorageService;

    /**
     * AdMeta constructor.
     * @param cURL $curl
     * @param LoggerInterface $logger
     * @param FileStorageServiceInterface $fileStorageService
     */
    public function __construct(cURL $curl, LoggerInterface $logger, FileStorageServiceInterface $fileStorageService)
    {
        $this->logger = $logger;
        $this->curl = $curl;
        $this->fileStorageService = $fileStorageService;
    }

    /**
     * @param ConfigInterface $config
     * @throws BadResponseException
     * @throws \Exception
     */
    public function run(ConfigInterface $config)
    {
        $username = $config->getParamValue('username', null);
        $password = $config->getParamValue('password', null);

        //// important: try get startDate, endDate by backFill
        if ($config->isNeedRunBackFill()) {
            $startDate = $config->getStartDateFromBackFill();

            if (!$startDate instanceof \DateTime) {
                $this->logger->error('need run backFill but backFillStartDate is invalid');
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
                $startDateStr = $config->getParamValue('startDate', 'yesterday');
                $endDateStr = $config->getParamValue('endDate', 'yesterday');
            }
        }

        $query = [
            'date-from' => $startDateStr,
            'date-to' => $endDateStr,
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

        $data = $this->doGet($url, $username, $password);

        if ($this->logger) {
            $this->logger->info('Finished fetching report data');
        }

        $fileName = sprintf('%s.csv', bin2hex(random_bytes(10)));
        $filePath = $this->fileStorageService->getDownloadPath($config, $fileName);
        $this->fileStorageService->saveToCSVFile($filePath, $data, null);
    }

    /**
     * @param $username
     * @param $password
     * @return string
     * @throws BadResponseException
     */
    public function getAdvertisers($username, $password)
    {
        return $this->doGet($this->getLiveApiUrl('advertisers'), $username, $password);
    }

    /**
     * @param $username
     * @param $password
     * @return string
     * @throws BadResponseException
     */
    public function getAgencies($username, $password)
    {
        return $this->doGet($this->getLiveApiUrl('agencies'), $username, $password);
    }

    /**
     * @param $username
     * @param $password
     * @return string
     * @throws BadResponseException
     */
    public function getSubPublishers($username, $password)
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

        return $this->doGet($url, $username, $password);
    }

    /**
     * @param $username
     * @param $password
     * @return string
     * @throws BadResponseException
     */
    public function getUsers($username, $password)
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

        return $this->doGet($url, $username, $password);
    }

    /**
     * @param $username
     * @param $password
     * @return string
     * @throws BadResponseException
     */
    public function getSites($username, $password)
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

        return $this->doGet($url, $username, $password);
    }

    /**
     * @param $username
     * @param $password
     * @return string
     * @throws BadResponseException
     */
    public function getPlacements($username, $password)
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

        return $this->doGet($url, $username, $password);
    }

    /**
     * @param DateTime $date
     * @param $username
     * @param $password
     * @return string
     * @throws BadResponseException
     */
    public function getReports(DateTime $date = null, $username, $password)
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

        $data = $this->doGet($url, $username, $password);

        if ($this->logger) {
            $this->logger->info('Finished fetching report data');
        }

        return $data;
    }

    /**
     * @param string $url
     * @param $username
     * @param $password
     * @return string
     * @throws BadResponseException
     */
    protected function doGet($url, $username, $password)
    {
        /** @var Request $request */
        $request = $this->curl
            ->newRequest('get', $url)
            ->auth($username, $password);

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

    /**
     * @return cURL
     */
    public function getCurl()
    {
        return $this->curl;
    }
}