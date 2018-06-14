<?php

namespace Tagcade\Service\Integration\Integrations\DemandPartner\NativoApi;

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

class NativoApi extends IntegrationAbstract implements IntegrationInterface
{
    /*
     * Command to create:
     * php app/console ur:integration:create nativo-api "Nativo API" -a -p "apiToken,secret:secure,dateRange:dynamicDateRange" -vv
     */

    const INTEGRATION_C_NAME = 'nativo-api';

    /* params from integration */
    const PARAM_BASE_URL = 'https://api.nativo.net/v2/data/marketplace';
    const PARAM_ENDPOINT_REPORTS = 'backstage/api/1.0/%s/reports/%s/dimensions/%s';


    const PARAM_API_TOKEN = 'apiToken';
    const PARAM_SECRET = 'secret';


    const PARAM_DIMENSIONS = 'dimensions';
    const PARAM_METRICS = 'metrics';

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

        $apiToken = $config->getParamValue(self::PARAM_API_TOKEN, null);
        $secret = $config->getParamValue(self::PARAM_SECRET, null);
        $rawDimensions = $config->getParamValue(self::PARAM_DIMENSIONS, null);
        $rawMetrics = $config->getParamValue(self::PARAM_METRICS, null);

        $dimensions = [];
        foreach ($rawDimensions as $dimension) {
            array_push($dimensions, strtolower(str_replace(' ', '_', $dimension)));
        }

        $metrics = [];
        foreach ($rawMetrics as $metric) {
            array_push($metrics, strtolower(str_replace(' ', '_', $metric)));
        }


        list($hash, $now) = $this->createHash($apiToken, $secret, $params);

        $startDate = $params->getStartDate();
        $endDate = $params->getEndDate();

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

                $dataArray = array(
                    "start_date" => $startDate->format('Y-m-d'),
//                    "end_date" => "2018-05-30",
                    "end_date" => $endDate->format('Y-m-d'),
                    "breakdown" => $dimensions,
                    "metrics" => $metrics,
                    "resolution" => "daily",
                    "page_size" => 1000,
                    "page" => 1,
//                    "timezone" => "US/Pacific"
                );


                list($responseData, $responseNames) = $this->getReport(
                    $apiToken,
                    $hash,
                    $now,
                    $params,
                    $dataArray
                );


                $dataRows = $responseData;
                if ($countHead == 0) {
                    $columnNames[] = $responseNames;
                }

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
                $this->fileStorage->saveToCSVFile($path, $dataRows, $columnNames);

                $params->setStartDate($startDate);
                $params->setEndDate($endDate);
                // add startDate endDate to Downloaded file name
                $this->downloadFileHelper->addStartDateEndDateToDownloadFiles($downloadFolderPath, $params);

                $this->downloadFileHelper->saveMetaDataFile($params, $downloadFolderPath);

                $countHead++;
            }

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
     * @param String $apiToken
     * @param String $secret
     * @param PartnerParamInterface $params
     * @return mixed
     */
    function createHash(String $apiToken, String $secret, PartnerParamInterface $params): array
    {
        $date = new DateTime();
        $now = $date->getTimestamp();
        $secretToBeHashed = utf8_encode($secret.$now);
        $hashSecret = hash_hmac('sha256', $secretToBeHashed, $apiToken, true);

        return [bin2hex($hashSecret), $now];
    }

    /**
     * @param String $apiToken
     * @param String $hash
     * @param String $now
     * @param PartnerParamInterface $params
     * @param $dataArray
     * @return array
     */
    function getReport(
        String $apiToken,
        String $hash,
        String $now,
        PartnerParamInterface $params,
        $dataArray
    ): array {
        $this->logger->info("Making GET request for report");

        $request = $this->curl->newJsonRequest('post', self::PARAM_BASE_URL, $dataArray);
        $request->setHeader('Content-Type', 'application/json');
        $request->setHeader('nativo-token', $apiToken);
        $request->setHeader('nativo-timestamp', $now);
        $request->setHeader('nativo-hash', $hash);

        $response = $this->curl->sendRequest($request);

        $this->checkStatus($params, $response->statusCode, 'getting report');

        $rawData = \GuzzleHttp\json_decode($response->body, true)["data"];

        $data = [];
        foreach ($rawData as $rawDatum) {
            $adType = $rawDatum["ad type"];
            $rawDatum["ad type"] = $adType["name"];

            $publication = $rawDatum["publication"];
            $rawDatum["publication"] = $publication["name"];
            array_push($data, $rawDatum);
        }

        $names = array(
            "Date", "Ad Type", "Publication","Viewable Impressions", "Revenue", "evCPM"
        );

        return [$data, $names];
    }

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