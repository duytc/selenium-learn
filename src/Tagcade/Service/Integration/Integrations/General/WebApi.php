<?php

namespace Tagcade\Service\Integration\Integrations\General;

use DateTime;
use Exception;
use Psr\Log\LoggerInterface;
use Tagcade\Service\FileStorageServiceInterface;
use Tagcade\Service\Integration\Config;
use Tagcade\Service\Integration\ConfigInterface;
use Tagcade\Service\Integration\Integrations\IntegrationAbstract;
use Tagcade\Service\Integration\Integrations\IntegrationInterface;

class WebApi extends IntegrationAbstract implements IntegrationInterface
{
    const INTEGRATION_C_NAME = 'general-web-api';

    /* params from integration */
    const PARAM_AUTH_URL = 'authUrl'; // url for authentication if need
    const PARAM_USER_NAME = 'username';
    const PARAM_PASSWORD = 'password';

    const PARAM_REPORT_URL = 'reportUrl'; // url for getting reports
    const PARAM_API_TOKEN = 'apiToken';
    const PARAM_START_DATE = 'startDate';
    const PARAM_END_DATE = 'endDate';
    const PARAM_DATE_RANGE = 'dateRange';
    const PARAM_PARAMS = 'params';

    /* macros for replace values of url */
    const MACRO_API_TOKEN = '{API_TOKEN}';
    const MACRO_START_DATE = '{START_DATE}';
    const MACRO_END_DATE = '{END_DATE}';

    const XLS_CONTENT_TYPE = 'application/vnd.ms-excel';
    const XLSX_CONTENT_TYPE = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
    const XML_CONTENT_TYPE = 'application/xml';
    const JSON_CONTENT_TYPE = 'application/json';
    const CSV_CONTENT_TYPE = 'text/csv';

    const URL = null;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var FileStorageServiceInterface
     */
    protected $fileStorage;

    /**
     * @var string
     */
    protected $authUrl;
    protected $username;
    protected $password;
    protected $reportUrl;
    protected $apiToken;
    /**
     * @var DateTime
     */
    protected $startDate;
    /**
     * @var DateTime
     */
    protected $endDate;
    protected $params;

    /**
     * @var ConfigInterface $config
     */
    protected $config;

    /**
     * GeneralIntegrationAbstract constructor.
     * @param LoggerInterface $logger
     * @param FileStorageServiceInterface $fileStorage
     */
    public function __construct(LoggerInterface $logger, FileStorageServiceInterface $fileStorage)
    {
        $this->logger = $logger;
        $this->fileStorage = $fileStorage;
    }

    /**
     * @inheritdoc
     */
    public function run(ConfigInterface $config)
    {
        $this->config = $config;
        // get all params
        $this->authUrl = $config->getParamValue(self::PARAM_AUTH_URL, null);
        $this->username = $config->getParamValue(self::PARAM_USER_NAME, null);
        $this->password = $config->getParamValue(self::PARAM_PASSWORD, null);

        $this->reportUrl = $config->getParamValue(self::PARAM_REPORT_URL, null);
        $this->apiToken = $config->getParamValue(self::PARAM_API_TOKEN, null);
        $this->params = $config->getParamValue(self::PARAM_PARAMS, null);

        //// important: try get startDate, endDate by backFill
        $startDateEndDate = $config->getStartDateEndDate();
        // todo: validate
        $this->startDate = $startDateEndDate[Config::PARAM_START_DATE];
        $this->endDate = $startDateEndDate[Config::PARAM_END_DATE];

        $queryParams = $this->createParams();

        $this->doGetData($queryParams);
    }

    /**
     * @throws Exception
     */
    protected function createParams()
    {
        // replace macros
        $replacedParams = $this->params;
        $replacedParams = str_replace(self::MACRO_API_TOKEN, $this->getApiToken(), $replacedParams);
        $replacedParams = str_replace(self::MACRO_START_DATE, $this->getStartDateString(), $replacedParams);
        $replacedParams = str_replace(self::MACRO_END_DATE, $this->getEndDateString(), $replacedParams);

        // parse query string to array
        $allParams = \GuzzleHttp\Psr7\parse_query($replacedParams);

        return $allParams;
    }

    /**
     * @return mixed
     */
    protected function getApiToken()
    {
        return $this->apiToken;
    }

    public function getStartDateString($format = 'Y-m-d')
    {
        return $this->startDate->format($format);
    }

    public function getEndDateString($format = 'Y-m-d')
    {
        return $this->endDate->format($format);
    }

    /**
     * @return array
     */
    public function getHeader()
    {
        return [];
    }

    /**
     * @return string
     */
    public function getMethod()
    {
        return 'GET';
    }

    /**
     * do get data for this by a paramsgetHeader(
     *
     * @param array $params built from this params and other params
     * @throws Exception
     */
    protected function doGetData($params = array())
    {
        $curl = curl_init();

        $responseData = $this->executeQuery($curl, $this->reportUrl, $this->getMethod(), $this->getHeader(), $params);

        $this->handleResponse($curl, $responseData);

        // close curl
        if (null !== $curl) {
            curl_close($curl);
            $curl = null;
        }
    }

    /**
     * @param resource $curl
     * @param string $url
     * @param string $method
     * @param array $header
     * @param array $data
     * @param array $auth
     * @return mixed
     */
    public function executeQuery($curl, $url, $method = 'GET', $header = array(), $data = array(), $auth = array())
    {
        if ($method == 'GET')
            $url = $url . '?' . http_build_query($data);

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        if (!empty($auth)) {
            curl_setopt($curl, CURLOPT_HTTPAUTH, $auth['CURLOPT_HTTPAUTH']);
            curl_setopt($curl, CURLOPT_USERPWD, $auth['username'] . ':' . $auth['password']);
        }

        if ($method == 'POST') {
            curl_setopt($curl, CURLOPT_POST, true);
            if (!empty($data)) {
                curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
            } else {
                curl_setopt($curl, CURLOPT_POSTFIELDS, array());
            }
        } elseif ($method == 'PUT') {
            curl_setopt($curl, CURLOPT_PUT, true);
            if (!empty($data)) {
                curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
            } else {
                curl_setopt($curl, CURLOPT_POSTFIELDS, array());
            }
        } elseif ($method == 'DELETE') {
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
            if (!empty($data)) {
                curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
            } else {
                curl_setopt($curl, CURLOPT_POSTFIELDS, array());
            }
        }

        return curl_exec($curl);
    }

    /**
     * @param resource $curl
     * @param $responseData
     * @throws Exception
     */
    protected function handleResponse($curl, $responseData)
    {
        $curlHttpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        if ($curlHttpCode !== 200) {
            throw new Exception(sprintf('cannot get data from this url, errorCode= %s', $curlHttpCode));
        }

        $curlContentType = curl_getinfo($curl, CURLINFO_CONTENT_TYPE);

        switch ($curlContentType) {
            case self::XLSX_CONTENT_TYPE:
                $fileType = '.xlsx';
                break;
            case self::XLS_CONTENT_TYPE:
                $fileType = '.xls';
                break;
            case self::XML_CONTENT_TYPE:
                $fileType = '.xml';
                break;
            case self::JSON_CONTENT_TYPE:
                $fileType = '.json';
                break;
            case self::CSV_CONTENT_TYPE:
                $fileType = '.csv';
                break;
            default:
                $fileType = '.txt';
        }

        $fileName = sprintf('%s_%d%s', 'file', strtotime(date('Y-m-d')), $fileType);
        $path = $this->fileStorage->getDownloadPath($this->config, $fileName);
        file_put_contents($path, $responseData);
    }
}