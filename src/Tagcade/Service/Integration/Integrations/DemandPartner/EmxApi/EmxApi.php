<?php

namespace Tagcade\Service\Integration\Integrations\DemandPartner\EmxApi;

use DateInterval;
use DatePeriod;
use DateTime;
use Exception;
use Psr\Log\LoggerInterface;
use Tagcade\Exception\LoginFailException;
use Tagcade\Exception\RuntimeException;
use Tagcade\Service\Core\TagcadeRestClientInterface;
use Tagcade\Service\DownloadFileHelper;
use Tagcade\Service\Fetcher\Params\PartnerParamInterface;
use Tagcade\Service\Fetcher\Params\PartnerParams;
use Tagcade\Service\FileStorageServiceInterface;
use Tagcade\Service\Integration\ConfigInterface;
use Tagcade\Service\Integration\Integrations\IntegrationAbstract;
use Tagcade\Service\Integration\Integrations\IntegrationInterface;
use anlutro\cURL\cURL;

class EmxApi extends IntegrationAbstract implements IntegrationInterface
{
    /*
     * Command to create:
     * php app/console ur:integration:create emx-api "EMX API" -a -p "username:plainText,password:secure,dimensions:multiOptions:day;Publisher Id;Geo Country;Placement Name;Brand Name;Supply Type;Size;Site Name,metrics:multiOptions:Imps Total;Publisher Revenue;Imps Filled;Publisher RPM;Imp Requests,reportType:option:Publisher Analytics,timezone:option:US/Eastern" -vv
     */

    const INTEGRATION_C_NAME = 'emx-api';

    /* params from integration */
    const PARAM_AUTH_URL = 'https://api.appnexus.com/auth';
    const PARAM_REPORT_URL = 'https://api.appnexus.com/report';

    const PARAM_USERNAME = 'username';
    const PARAM_PASSWORD = 'password';
    const PARAM_REPORT_TYPE = 'reportType';
    const PARAM_DIMENSIONS = 'dimensions';
    const PARAM_METRICS = 'metrics';
    const PARAM_TIMEZONE = 'timezone';
    const CSV_CONTENT_TYPE = 'text/csv';

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var DownloadFileHelper
     */
    private $downloadFileHelper;

    /**
     * @var cUrl
     */
    protected $curl;

    /**
     * @var FileStorageServiceInterface
     */
    protected $fileStorage;

    /** @var TagcadeRestClientInterface */
    protected $restClient;

    /**
     * GeneralIntegrationAbstract constructor.
     * @param LoggerInterface $logger
     * @param DownloadFileHelper $downloadFileHelper
     * @param FileStorageServiceInterface $fileStorage
     * @param TagcadeRestClientInterface $restClient
     * @param cURL $curl
     */
    public function __construct(
        LoggerInterface $logger,
        DownloadFileHelper $downloadFileHelper,
        FileStorageServiceInterface $fileStorage,
        TagcadeRestClientInterface $restClient,
        cURL $curl
    ) {
        $this->logger = $logger;
        $this->downloadFileHelper = $downloadFileHelper;
        $this->fileStorage = $fileStorage;
        $this->restClient = $restClient;
        $this->curl = $curl;
    }

    /**
     * @inheritdoc
     */
    public function run(ConfigInterface $config)
    {
        $params = new PartnerParams($config);

        $username = $config->getParamValue(self::PARAM_USERNAME, null);
        $password = $config->getParamValue(self::PARAM_PASSWORD, null);
        $dimensions = $config->getParamValue(self::PARAM_DIMENSIONS, null);
        $metrics = $config->getParamValue(self::PARAM_METRICS, null);
        $timezone = $config->getParamValue(self::PARAM_TIMEZONE, null);

        $columns = [];

        foreach ($dimensions as $dimension) {
            array_push($columns, strtolower(str_replace(' ', '_', $dimension)));
        }

        foreach ($metrics as $metric) {
            array_push($columns, strtolower(str_replace(' ', '_', $metric)));
        }

        $reportType = strtolower(str_replace(' ', '_', $config->getParamValue(self::PARAM_REPORT_TYPE, null)));


//        $sessionKey = '';
        $sessionKey = $this->getLogin($username, $password, $params);

        $startDate = $params->getStartDate();
        $endDate = $params->getEndDate();

        $fileName = sprintf(
            '%s_%s_%d%s',
            'file',
            (new DateTime())->getTimestamp(),
            strtotime(date('Y-m-d')),
            $this->downloadFileHelper->getFileExtension(self::CSV_CONTENT_TYPE)
        );
        // important: each file will be stored in separated dir,
        // then metadata is stored in same this dir
        // so that we know file and metadata file is in pair

        $endDate = $endDate->modify('+1 day'); // add 1 day for DateInterval correctly loop from startDate to endDate
        $interval = new DateInterval('P1D');
        $dateRange = new DatePeriod($startDate, $interval, $endDate);

        try {
            $this->logger->debug('Starting download file');
            foreach ($dateRange as $i => $singleDate) {
                if (!$singleDate instanceof DateTime) {
                    continue;
                }

                $startDate = clone $singleDate;
                $endDate = $singleDate->modify("+1 day");

                $responseData = $this->getReport(
                    $sessionKey,
                    $params,
                    $startDate->format('Y-m-d'),
                    $endDate->format('Y-m-d'),
                    $columns,
                    $reportType,
                    $timezone
                );

                $fileName = sprintf(
                    '%s_%s_%d%s',
                    'file',
                    (new DateTime())->getTimestamp(),
                    strtotime(date('Y-m-d')),
                    $this->downloadFileHelper->getFileExtension(self::CSV_CONTENT_TYPE)
                );

                // important: each file will be stored in separated dir,
                // then metadata is stored in same this dir
                // so that we know file and metadata file is in pair
                $subDir = sprintf('%s-%s', $startDate->format("Ymd"), $endDate->format("Ymd"));
                $downloadFolderPath = $this->fileStorage->getDownloadPath($config, '', $subDir);

                $path = $this->fileStorage->getDownloadPath($config, $fileName, $subDir);

                $this->logger->debug('Save download file');
                $f = fopen($path, 'w');
                fwrite($f, $responseData);
                fclose($f);
            }

            // reset endDate
            $params->setEndDate($endDate);

            $this->restClient->updateIntegrationWhenDownloadSuccess(new PartnerParams($config));
        } catch (RuntimeException $runTimeException) {
            $this->restClient->createAlertWhenTimeOut(
                $params->getPublisherId(),
                $params->getIntegrationCName(),
                $params->getDataSourceId(),
                $params->getStartDate(),
                $params->getEndDate(),
                date("Y-m-d H:i:s")
            );

            throw new RuntimeException($runTimeException->getMessage());
        } catch (LoginFailException $loginFailException) {

            $this->restClient->createAlertWhenLoginFail(
                $loginFailException->getPublisherId(),
                $loginFailException->getIntegrationCName(),
                $loginFailException->getDataSourceId(),
                $loginFailException->getStartDate(),
                $loginFailException->getEndDate(),
                $loginFailException->getExecutionDate()
            );

            // re-throw for retry handle
            throw $loginFailException;
        } catch (Exception $e) {
            $message = $e->getMessage() ? $e->getMessage() : $e->getTraceAsString();
            $this->logger->critical($message);

            // re-throw for retry handle
            throw $e;
        }
    }

    /**
     * @param $username
     * @param $password
     * @param PartnerParamInterface $params
     * @return mixed
     */
    function getLogin($username, $password, PartnerParamInterface $params): string
    {
        $this->logger->info("Getting auth token");

        $jsonAuth = \GuzzleHttp\json_encode(
            [
                "auth" => [
                    "username" => $username,
                    "password" => $password,
                ],
            ]
        );

        $request = $this->curl->newRequest('post', self::PARAM_AUTH_URL, $jsonAuth, 2);

        $response = $this->curl->sendRequest($request);

        $this->checkStatus($params, $response->statusCode, 'login', 200);

        $authToken = \GuzzleHttp\json_decode($response->body, true)["response"]["token"];

        return $authToken;
    }

    /**
     * @param $accessToken
     * @param PartnerParamInterface $params
     * @param $startDate
     * @param $endDate
     * @param $columns
     * @param $reportType
     * @param $timezone
     * @return string
     */
    function createReport($accessToken, PartnerParamInterface $params, $startDate, $endDate, $columns, $reportType, $timezone):string
    {
        $this->logger->info("Create report");

        $requestBody = \GuzzleHttp\json_encode(["report" => [
            "report_type" => $reportType,
            "start_date" => $startDate . ' 00:00:00',
            "end_date" => $endDate . ' 00:00:00',
            "columns" => $columns,
            "format" => 'csv',
            "timezone" => $timezone
        ]]);

        $request = $this->curl->newRequest('post', self::PARAM_REPORT_URL, $requestBody, 2);
        $request->setHeader("Authorization", $accessToken);
        $response = $this->curl->sendRequest($request);

        $this->checkStatus($params, $response->statusCode, 'getting report');

        return \GuzzleHttp\json_decode($response->body, true)["response"]["report_id"];

    }

    /**
     * @param $accessToken
     * @param PartnerParamInterface $params
     * @param $startDate
     * @param $endDate
     * @param $columns
     * @param $reportType
     * @param $timezone
     * @return string
     */
    function getReport($accessToken, PartnerParamInterface $params, $startDate, $endDate, $columns, $reportType, $timezone): string
    {

//        $accessToken = 'authn:227525:c26898614d153:nym2';

        $reportId = $this->createReport($accessToken, $params, $startDate, $endDate, $columns, $reportType, $timezone);

//        $reportId = "28b9f93414be89e1f1e0dc3647361cab";
        $this->logger->info("Making Report Request and Checking Status");
        $seconds = 10;

        $status = null;

        while($status !== 'ready') {
            $statusRequest = $this->curl->newRawRequest('get', self::PARAM_REPORT_URL . '?id=' . $reportId);
            $statusRequest->setHeader("Authorization", $accessToken);
            $response = $this->curl->sendRequest($statusRequest);

            $this->checkStatus($params, $response->statusCode, 'checking if report ready');

            $status = \GuzzleHttp\json_decode($response->body, true)["response"]["execution_status"];
            $this->logger->info("Sleeping for $seconds seconds.");
            sleep($seconds);
            $seconds = $seconds*2;

        }

        $downloadUrl = str_replace("report","", \GuzzleHttp\json_decode($response->body, true)["response"]["report"]["url"]);

        $dlRequest = $this->curl->newRawRequest('get', self::PARAM_REPORT_URL . $downloadUrl);
        $dlRequest->setHeader("Authorization", $accessToken);
        $dlResponse = $this->curl->sendRequest($dlRequest);

        $this->checkStatus($params, $dlResponse->statusCode, 'getting report');

        $data = $dlResponse->body;

        return $data;
    }

    /**
     * check if code is 419..session expired
     */
    /**
     * @param PartnerParamInterface $params
     * @param $code
     * @param $source
     * @param int $expectedCode
     */
    private function checkStatus(PartnerParamInterface $params, $code, $source, $expectedCode = 200)
    {
        if ($code !== $expectedCode) {
            // will be retry
            if ($code == 419) {
                die("Session Expired");
            }
            throw new RuntimeException(
                sprintf('Cannot get data from this url, errorCode = %s while doing %s', $code, $source)
            );

        }
    }
}