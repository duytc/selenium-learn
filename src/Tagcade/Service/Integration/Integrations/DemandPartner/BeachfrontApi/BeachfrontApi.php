<?php

namespace Tagcade\Service\Integration\Integrations\DemandPartner\BeachfrontApi;

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

class BeachfrontApi extends IntegrationAbstract implements IntegrationInterface
{
    /*
     * Command to create:
     * php app/console ur:integration:create beachfront-api "Beachfront API" -a -p apiKey:secure,dateRange:dynamicDateRange,dimensions:multiOptions:Date;Domain;Country;Ad ID;Campaign ID;Campaign;Inventory Name;Inventory ID;Marketplace;Marketplace ID;Platform;Publisher;Player Size;Bundle,metrics:multiOptions:Impressions;CPM;Click Through Rate;View Completion Rate;Clicks;Attempts;Revenue;Requests;CPM Publisher;Revenue Publisher -vv
     */

    const INTEGRATION_C_NAME = 'beachfront-api';

    /* params from integration */
    const PARAM_API_BASE_URL = 'http://api.public.bfmio.com/api/report/generate';
    const PARAM_API_KEY = 'apiKey';
    const PARAM_METRICS = 'metrics';
    const PARAM_DIMENSIONS = 'dimensions';

    const METRICS = [
        "Click Through Rate" => "ctr",
        "View Completion Rate" => "vcr",
        "Attempts" => "adattempts"
    ];
    const DIMENSIONS = [
        "Date" => "day",
        "Inventory ID" => "appid",
        "Player Size" => "playerSize",
        "Marketplace ID" => "marketid",
        "Inventory Name" => "inventory"
    ];

    const CSV_CONTENT_TYPE = 'text/csv';

    protected $fillRate = false;

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

        $apiKey = $config->getParamValue(self::PARAM_API_KEY, null);
        $rawDimensions = $config->getParamValue(self::PARAM_DIMENSIONS, null);
        $rawMetrics = $config->getParamValue(self::PARAM_METRICS, null);

        $dimensions = [];
        foreach ($rawDimensions as $dimension) {
            $self = self::DIMENSIONS;
            if (array_key_exists($dimension, self::DIMENSIONS)) {
                array_push($dimensions, self::DIMENSIONS[$dimension]);
            }
            else {
                array_push($dimensions, strtolower(str_replace(' ', '', $dimension)));
            }

        }

        $headers = array_merge($rawDimensions, $rawMetrics);

        $metrics = [];
        foreach ($rawMetrics as $metric) {
            if ($metric === "Fill Rate"){
                $this->fillRate = true;
                continue;
            }
            else if (array_key_exists($metric, self::METRICS)) {
                array_push($metrics, self::METRICS[$metric]);
            }
            else {
                array_push($metrics, strtolower(str_replace(' ', '', $metric)));
            }

        }

        $startDate = $params->getStartDate();
        $endDate = $params->getEndDate();

        $endDate = $endDate->modify('+1 day'); // add 1 day for DateInterval correctly loop from startDate to endDate
        $interval = new DateInterval('P1D');
        $dateRange = new DatePeriod($startDate, $interval, $endDate);

        // important: each file will be stored in separated dir,
        // then metadata is stored in same this dir
        // so that we know file and metadata file is in pair
        $subDir = sprintf('%s-%s', $startDate->format("Ymd"), $endDate->format("Ymd"));
        $downloadFolderPath = $this->fileStorage->getDownloadPath($config, '', $subDir);

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
                    "keys" => $dimensions,
                    "metrics" => $metrics,
                    "fromDate" => $startDate->format("Y-m-d"),
                    "toDate" => $endDate->format("Y-m-d")
                );
                list($responseData, $responseDataColumns) = $this->createReport(self::PARAM_API_BASE_URL, $apiKey, $params, $data);

                $dataRows = $responseData;
                if ($countHead == 0) {
                    $columnNames[] = $headers;
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
            }
            // reset endDate
            $params->setEndDate($endDate);
            // create metadata file. metadata file contains file pattern, so it lets directory monitory has information to get exact data source relates to file pattern
            $this->downloadFileHelper->saveMetaDataFile($params, $downloadFolderPath);

            // add startDate endDate to Downloaded file name
            $this->downloadFileHelper->addStartDateEndDateToDownloadFiles($downloadFolderPath, $params);

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
     * @param $url
     * @param $accessToken
     * @param PartnerParamInterface $params
     * @param $data
     * @return array
     * @throws LoginFailException
     */
    function createReport($url, $accessToken, PartnerParamInterface $params, $data):array
    {
        $request = $this->curl->newJsonRequest('post', $url, $data);
        $request->setHeader("token", $accessToken);
        $response = $this->curl->sendRequest($request);

        $this->checkStatus($params, $response->statusCode);

        $report = \GuzzleHttp\json_decode($response->body, true);

        if ($report["status"] !== "SUCCESS") {
            $e = $report['errorDetails'];
            $this->logger->alert("No Report was returned with error: $e");
            die;
        }
        $headLine = $report["columns"];
        $dataLine = [];


        $keyOrder = [];
        foreach ($data["keys"] as $item) {
            array_push($keyOrder, array_search($item, $headLine));
        }

        foreach ($data["metrics"] as $item) {
            array_push($keyOrder, array_search($item, $headLine));
        }

        if ($this->fillRate) {
            $impKey = array_search("impressions", $headLine);
            $reqKey = array_search("requests", $headLine);
            $fillLoc = max(array_search("impressions", $data["metrics"]), array_search("requests", $data["metrics"])) + sizeof($data["keys"]) + 1;
        }

        foreach ($report["data"] as $item) {
            $properData = [];
            foreach ($keyOrder as $key) {
                array_push($properData, $item[$key]);
            }

            if ($this->fillRate) {
                $fillRate = 0;
                if ($item[$impKey] !== 0) {
                    $fill = $item[$impKey] / $item[$reqKey];
                    $fillRate = number_format($fill, 2);
                }
                array_splice($properData, $fillLoc, 0, $fillRate);
            }

            $dataLine[] = $properData;
        }

        return [$dataLine, $headLine];
    }

    /**
     * @param PartnerParamInterface $params
     * @param $code
     * @throws LoginFailException
     */
    private function checkStatus(PartnerParamInterface $params, $code)
    {
        if ($code !== 200) {
            // will be retry
            if ($code >= 400 && $code < 500) {
                throw new LoginFailException(
                    $params->getPublisherId(),
                    $params->getIntegrationCName(),
                    $params->getDataSourceId(),
                    $params->getStartDate(),
                    $params->getEndDate(),
                    new \DateTime()
                );
            } else {
                throw new RuntimeException(sprintf('Cannot get data from this url, errorCode = %s', $code));
            }
        }
    }
}