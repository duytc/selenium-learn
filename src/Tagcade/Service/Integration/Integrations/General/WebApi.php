<?php

namespace Tagcade\Service\Integration\Integrations\General;

use DateTime;
use Exception;
use Psr\Log\LoggerInterface;
use RestClient\CurlRestClient;
use Tagcade\Service\FileStorageServiceInterface;
use Tagcade\Service\Integration\Config;
use Tagcade\Service\Integration\ConfigInterface;
use Tagcade\Service\Integration\Integrations\IntegrationAbstract;
use Tagcade\Service\Integration\Integrations\IntegrationInterface;

class WebApi extends IntegrationAbstract implements IntegrationInterface
{
    const INTEGRATION_C_NAME = 'web-api';

    /* params from integration */
    const PARAM_AUTH_URL = 'authUrl'; // url for authentication if need
    const PARAM_USER_NAME = 'username';
    const PARAM_PASSWORD = 'password';

    const PARAM_REPORT_URL = 'reportUrl'; // url for getting reports
    const PARAM_API_TOKEN = 'apitoken';
    const PARAM_START_DATE = 'startDate';
    const PARAM_END_DATE = 'endDate';
    const PARAM_DATE_RANGE = 'dateRange';
    const PARAM_PARAMS = 'params';

    /* macros for replace values of url */
    const MACRO_API_TOKEN = '{API_TOKEN}';
    const MACRO_START_DATE = '{START_DATE}';
    const MACRO_END_DATE = '{END_DATE}';

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
        $this->reportUrl = $config->getParamValue(self::PARAM_REPORT_URL, null);
        $this->authUrl = $config->getParamValue(self::PARAM_AUTH_URL, null);

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
        $replacedParams = str_replace(self::MACRO_API_TOKEN, $this->apiToken, $replacedParams);
        $replacedParams = str_replace(self::MACRO_START_DATE, $this->getStartDateString(), $replacedParams);
        $replacedParams = str_replace(self::MACRO_END_DATE, $this->getEndDateString(), $replacedParams);

        // parse query string to array
        $allParams = \GuzzleHttp\Psr7\parse_query($replacedParams);

        return $allParams;
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
     * @return null
     */
    public function getHeader()
    {
        return null;
    }

    /**
     * @return string
     */
    public function getMethod()
    {
        return 'GET';
    }

    /**
     * do get data for this by a params
     *
     * @param array $params built from this params and other params
     * @return mixed
     */
    protected function doGetData($params = array())
    {
        $curl = new CurlRestClient();
        $responseData = $curl->executeQuery($this->reportUrl, $this->getMethod(), $this->getHeader(), $params);
        $curl->close();
        $fileName = sprintf('%s_%d', 'file', strtotime(date('Y-m-d')));
        $path = $this->fileStorage->getDownloadPath($this->config, $fileName);
        file_put_contents($path, $responseData);
        return $responseData;
    }
}