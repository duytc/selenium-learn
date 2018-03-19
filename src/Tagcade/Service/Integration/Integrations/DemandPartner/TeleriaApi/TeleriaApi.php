<?php

namespace Tagcade\Service\Integration\Integrations\DemandPartner\TeleriaAPI;

use anlutro\cURL\cURL;
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

class TeleriaAPI extends IntegrationAbstract implements IntegrationInterface
{
    /*
     * Command to create:
     * php app/console ur:integration:create demand-partner-teleria-api "Teleria API" -a -p "accessKey,secretKey:secure,dateRange:dynamicDateRange,dimensions:multiOptions:Ad Unit;Demand;App Bundle Id,metrics:multiOptions:Impressions;Requests;Fill Rate;Use Rate;Starts;Q1s;Mids;Q3s;Clicks;Avg Completion Rate;Click Thru Rate;SSP Net Revenue;SSP Gross Revenue;SSP Net CPM;SSP Gross CPM;Currency;Year;Day" -vv
     */

    const INTEGRATION_C_NAME = 'demand-partner-teleria-api';

    /* params from integration */
    const PARAM_BASE_URL = 'https://api.tremorhub.com/v1/resources'; // url for getting session id
    const PARAM_ENDPOINT_SESSION = '/sessions';
    const PARAM_ENDPOINT_QUERIES = '/queries';
    const PARAM_ENDPOINT_STATUS = '/statuses';
    const PARAM_ENDPOINT_RESULTS = '/results';

    const PARAM_ACCESS_KEY = 'accessKey';
    const PARAM_SECRET_KEY = 'secretKey';
    const PARAM_DIMENSIONS = 'dimensions';
    const PARAM_METRICS = 'metrics';

    const METRICS = [
        "Q1s" => "firstQuartiles",
        "Mids" => "secondQuartiles",
        "Q3s" => "thirdQuartiles",
        "SSP Gross CPM" => "grossCpm",
        "SSP Net CPM" => "netCpm",
        "SSP Gross Revenue" => "sspGrossRevenue",
        "SSP Net Revenue" => "sspNetRevenue",
        "Click Thru Rate" => "ctr",
    ];
    const DIMENSIONS = [
        "Demand" => "network",
    ];

    const RESPONSE_ATTRIBUTES = '@attributes';
    const RESPONSE_BODY = 'body';
    const CSV_CONTENT_TYPE = 'text/csv';

    protected $headers;
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

        $accessKey = $config->getParamValue(self::PARAM_ACCESS_KEY, null);
        $secretKey = $config->getParamValue(self::PARAM_SECRET_KEY, null);
        $rawDimensions = $config->getParamValue(self::PARAM_DIMENSIONS, null);
        $rawMetrics = $config->getParamValue(self::PARAM_METRICS, null);

        $fields = [];
        foreach ($rawDimensions as $dimension) {
            if (array_key_exists(trim($dimension), self::DIMENSIONS)) {
                array_push($fields, self::DIMENSIONS[$dimension]);
            } else {
                array_push($fields, lcfirst(ucwords(str_replace(' ', '', $dimension))));
            }

        }

        foreach ($rawMetrics as $metric) {
            if (array_key_exists(trim($metric), self::METRICS)) {
                array_push($fields, self::METRICS[$metric]);
            } else {
                array_push($fields, lcfirst(ucwords(str_replace(' ', '', $metric))));
            }
        }

        $this->headers = array_merge($rawDimensions, $rawMetrics);

        $sessionKey = $this->getLogin(self::PARAM_ENDPOINT_SESSION, $accessKey, $secretKey, $params);

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
        $subDir = sprintf('%s-%s', $startDate->format("Ymd"), $endDate->format("Ymd"));
        $downloadFolderPath = $this->fileStorage->getDownloadPath($config, '', $subDir);
        $path = $this->fileStorage->getDownloadPath($config, $fileName, $subDir);

        $endDate = $endDate->modify('+1 day'); // add 1 day for DateInterval correctly loop from startDate to endDate
        $interval = new DateInterval('P1D');
        $dateRange = new DatePeriod($startDate, $interval, $endDate);

        try {
            $columnNames = [];
            $dataRows = [];
            $countHead = 0;
            $this->logger->debug('Starting download file');
            foreach ($dateRange as $i => $singleDate) {
                if (!$singleDate instanceof DateTime) {
                    continue;
                }

                $startDate = clone $singleDate;
                $endDate = $singleDate->modify("+1d");

                $data = array(
                    "source" => "adstats-publisher",
                    "fields" => $fields,
                    "range" => [
                        "fromDate" => $startDate->format("Y-m-d"),
                        "toDate" => $endDate->format("Y-m-d"),
                    ],
                );

                $reportId = $this->createReport(
                    self::PARAM_ENDPOINT_QUERIES,
                    $sessionKey,
                    $params,
                    $data
                );

                $responseData = $this->getReport($reportId, $sessionKey, $params);


                $dataRows = $responseData;
                if ($countHead == 0) {
                    $columnNames[] = $this->headers;
                }

                $fileName = sprintf(
                    '%s_%s_%d%s',
                    'file',
                    (new DateTime())->getTimestamp(),
                    strtotime(date('Y-m-d')),
                    $this->downloadFileHelper->getFileExtension(self::CSV_CONTENT_TYPE)
                );

                $path = $this->fileStorage->getDownloadPath($config, $fileName, $subDir);

                $this->logger->debug('Save download file');
                $this->fileStorage->saveToCSVFile($path, $dataRows, $columnNames);
                $countHead++;

                // add startDate endDate to Downloaded file name
                $this->downloadFileHelper->addStartDateEndDateToDownloadFiles($downloadFolderPath, $params);

            }

            // reset endDate
            $params->setEndDate($endDate);
            // create metadata file. metadata file contains file pattern, so it lets directory monitory has information to get exact data source relates to file pattern
            $this->downloadFileHelper->saveMetaDataFile($params, $downloadFolderPath);


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
     * @param $endpoint
     * @param $username
     * @param $password
     * @param PartnerParamInterface $params
     * @return mixed
     * @throws LoginFailException
     */
    function getLogin($endpoint, $username, $password, PartnerParamInterface $params): string
    {
        $data = [
            "accessKey" => $username,
            "secretKey" => $password,
        ];

        $request = $this->curl->newJsonRequest('post', self::PARAM_BASE_URL.$endpoint, $data);
        $request->setHeader('Content-Type', "application/json");
        $request->setHeader('Accept', "application/json");

        $response = $this->curl->sendRequest($request);

        $this->checkStatus($params, $response->statusCode, 'login', 201);

        $authToken = \GuzzleHttp\json_decode($response->body)->sessionCode;

        return $authToken;
    }

    /**
     * @param $url
     * @param $accessToken
     * @param PartnerParamInterface $params
     * @param $data
     * @return string
     * @throws LoginFailException
     */
    function createReport($url, $accessToken, PartnerParamInterface $params, $data): string
    {
        $request = $this->curl->newJsonRequest('post', self::PARAM_BASE_URL.$url, $data);
        $request->setCookie("ApiSession", $accessToken);
        $response = $this->curl->sendRequest($request);

        $this->checkStatus($params, $response->statusCode, 'create report');

        $report = \GuzzleHttp\json_decode($response->body, true);

        return $report["code"];
    }

    /**
     * @param $reportId
     * @param $accessToken
     * @param PartnerParamInterface $params
     * @return array
     */
    function getReport($reportId, $accessToken, PartnerParamInterface $params): array
    {
        $this->logger->info("Making Report Request and Checking Status");
        $seconds = 10;
        $this->logger->info("Sleeping for $seconds seconds.");
        sleep($seconds);

        $url = self::PARAM_BASE_URL.self::PARAM_ENDPOINT_QUERIES.'/'.$reportId;

        $request = $this->curl->newRawRequest('get', $url);
        $request->setCookie("ApiSession", $accessToken);
        $response = $this->curl->sendRequest($request);

        $this->checkStatus($params, $response->statusCode, 'getting report');

        $reportStatus = \GuzzleHttp\json_decode($response->body, true);

        while ($reportStatus["status"] == 2) {
            $seconds = $seconds * 2;
            $this->logger->info("Sleeping for $seconds seconds.");
            sleep($seconds);

            $response = $this->curl->sendRequest($request);
            $reportStatus = \GuzzleHttp\json_decode($response->body, true);
        }

        $request = $this->curl->newRawRequest('get', $url . self::PARAM_ENDPOINT_RESULTS);
        $request->setCookie("ApiSession", $accessToken);
        $response = $this->curl->sendRequest($request);

        $this->checkStatus($params, $response->statusCode, 'getting report');

        $rawData = \GuzzleHttp\json_decode($response->body, true);

        $data = [];
        foreach ($rawData as $rawDatum) {
            unset($rawDatum["adUnitIdLink"]);
            unset($rawDatum["networkIdLink"]);
            unset($rawDatum["currencyId"]);
            array_push($data, $rawDatum);
        }

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