<?php

namespace Tagcade\Service\Integration\Integrations\Video\StreamRailInternalApi;

use anlutro\cURL\cURL;
use DateInterval;
use DatePeriod;
use DateTime;
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

class StreamRailInternalApi extends IntegrationAbstract implements IntegrationInterface
{
    // php app/console ur:integration:create video-streamrail-internal-api "Streamrail Internal API" -a -p "username,password:secure,dateRange:dynamicDateRange,dimensions:multiOptions:Date;Environment;Hour;Supply Partner;Traffic Channel;Ad Source;Demand Partner"

    const INTEGRATION_C_NAME = 'video-streamrail-internal-api';

    /* params from integration */
    const PARAM_BASE_URL = 'https://partners.streamrail.com/api/v2/';
    const PARAM_AUTH_ENDPOINT = 'login';
    const PARAM_REPORT_ENDPOINT = 'custom-reports';
    const PARAM_EXPORT_URL = 'https://partners.streamrail.com/data-export/data/custom/';

    const PARAM_USERNAME = 'username';
    const PARAM_PASSWORD = 'password';
    const PARAM_DIMENSIONS = 'dimensions';

    const RESPONSE_ATTRIBUTES = '@attributes';
    const RESPONSE_BODY = 'body';
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

        foreach ($dimensions as $key => $dim) {
            if ($dim === 'Demand Partner') $dim = 'advertiser';
            if ($dim === 'Supply Partner') $dim = 'publisher';

            $dimensions[$key] = lcfirst(str_replace(' ', '', ucwords($dim)));
        }

        $dimStr = implode(",", $dimensions);

        $accessToken = $this->getLogin(self::PARAM_AUTH_ENDPOINT, $username, $password, $params);

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

                $fileName = sprintf(
                    '%s_%s_%d%s',
                    'file',
                    (new DateTime())->getTimestamp(),
                    strtotime(date('Y-m-d')),
                    '.zip'
                );

                $data = array("customReport" => array(
                    "name" => $fileName,
                    "status" => 0,
                    "reportType" => 0,
                    "dimensions" => $dimStr,
                    "dateRange" => "custom",
                    "startDate" => $startDate->format("Y-m-d"),
                    "endDate" => $endDate->format("Y-m-d"),
                    "metrics" => "requests,bids,wins,impressions,postBidErrors,demandRevenues,cost,profitMargin,successRate,ecpm,ctr,clicks,playerLoads,completionRate,completions,preBidErrors,profit"
                ));
                $reportId = $this->createReport(self::PARAM_REPORT_ENDPOINT, $accessToken, $params, $data);

                $responseData = $this->getReport($reportId, $accessToken, $params);

                $path = $this->fileStorage->getDownloadPath($config, $fileName, $subDir);

                $this->logger->debug('Save download file');
                $f = fopen($path, 'w');
                fwrite($f, $responseData);
                fclose($f);
                $countHead++;
            }
            // reset endDate
            $params->setEndDate($endDate);
            // create metadata file. metadata file contains file pattern, so it lets directory monitory has information to get exact data source relates to file pattern
            $this->downloadFileHelper->saveMetaDataFile($params, $downloadFolderPath);

            // add startDate endDate to Downloaded file name
            $this->downloadFileHelper->addStartDateEndDateToDownloadFiles($downloadFolderPath, $params);

            $this->restClient->updateIntegrationWhenDownloadSuccess(new PartnerParams($config));
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
    function getLogin($endpoint, $username, $password, PartnerParamInterface $params):string
    {
        $data = 'username='. $username . '&password=' . $password;

        $request = $this->curl->newRawRequest('post', self::PARAM_BASE_URL . $endpoint , $data);
        $response = $this->curl->sendRequest($request);

        $this->checkStatus($params, $response->statusCode, 'login');

        $authToken = \GuzzleHttp\json_decode($response->body)->access_token;

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
    function createReport($url, $accessToken, PartnerParamInterface $params, $data):string
    {
        $request = $this->curl->newJsonRequest('post', self::PARAM_BASE_URL . $url, $data);
        $request->setHeader("Authorization", "Bearer " . $accessToken);
        $response = $this->curl->sendRequest($request);

        $this->checkStatus($params, $response->statusCode, 'create report');

        $report = \GuzzleHttp\json_decode($response->body, true);

        return $report["customReport"]["id"];
    }

    /**
     * @param $reportId
     * @param $accessToken
     * @param PartnerParamInterface $params
     * @return string
     * @throws LoginFailException
     */
    function getReport($reportId, $accessToken, PartnerParamInterface $params):string
    {
        $this->logger->info("Making Report Request and Checking Status");
        $seconds = 10;
        sleep($seconds);

        $request = $this->curl->newRawRequest('get', self::PARAM_BASE_URL . self::PARAM_REPORT_ENDPOINT .'/' .  $reportId);
        $request->setHeader("Authorization", "Bearer " . $accessToken);
        $response = $this->curl->sendRequest($request);

        $this->checkStatus($params, $response->statusCode, 'getting report');

        $reportStatus = \GuzzleHttp\json_decode($response->body, true);

        while ($reportStatus["customReport"]["reportStatus"] !== "DONE") {
            $seconds = $seconds*2;
            $this->logger->info("Sleeping for $seconds seconds.");
            sleep($seconds);

            $response = $this->curl->sendRequest($request);
            $reportStatus = \GuzzleHttp\json_decode($response->body, true);
        }

        $request = $this->curl->newRawRequest('get', self::PARAM_EXPORT_URL . $reportId);
        $request->setHeader("Authorization", "Bearer " . $accessToken);
        $response = $this->curl->sendRequest($request);

        $result = explode('href=', $response->body);
        $link = \GuzzleHttp\json_decode(explode(">", $result[1])[0]);

        $rawData = file_get_contents($link);

        return $rawData;
    }

    /**
     * @param PartnerParamInterface $params
     * @param $code
     * @throws LoginFailException
     */
    private function checkStatus(PartnerParamInterface $params, $code, $source)
    {
        if ($code !== 200) {
            // will be retry
            throw new RuntimeException(sprintf('Cannot get data from this url, errorCode = %s while doing %s', $code, $source));

        }
    }
}