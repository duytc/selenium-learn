<?php

namespace Tagcade\Service\Integration\Integrations\Video\SpringServeApi;

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

class SpringServeApi extends IntegrationAbstract implements IntegrationInterface
{
    // php app/console ur:integration:create video-springserve-api "SpringServe API" -a -p "username,password:secure,dateRange:dynamicDateRange,account:regex,timezone:option:UTC;America/New York;America/Los Angeles,dimensions:multiOptions:Supply Tag ID;Supply Type;Supply Partner ID;Demand Tag ID;Demand Type;Demand Partner ID;Declared Domain;Detected Domain;Country;Declared Player Size;Detected Player Size"

    const INTEGRATION_C_NAME = 'video-springserve-api';

    /* params from integration */
    const PARAM_BASE_URL = 'https://video.springserve.com/api/v0/';
    const PARAM_AUTH_ENDPOINT = 'auth';
    const PARAM_REPORT_ENDPOINT = 'report';

    const PARAM_USERNAME = 'username';
    const PARAM_PASSWORD = 'password';
    const PARAM_DIMENSIONS = 'dimensions';
    const PARAM_ACCOUNT = 'account';

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
        $accountRaw = $config->getParamValue(self::PARAM_ACCOUNT, null);

        foreach ($dimensions as $key => $dimension) {
            $dimensions[$key] = strtolower(str_replace(' ', '_', $dimension));
        }

        $account = explode(':', $accountRaw)[1];

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

                $data = array(
                    "timezone" => 'UTC',
                    "interval" => 'day',
                    "dimensions" => $dimensions,
                    "start_date" => $startDate->format("Y-m-d"),
                    "end_date" => $endDate->format("Y-m-d"),
                    "account_id" => $account
                );
                list($responseData, $responseDataColumns) = $this->createReport(self::PARAM_REPORT_ENDPOINT, $accessToken, $params, $data);

                $dataRows = $responseData;
                if ($countHead == 0) {
                    $columnNames[] = $responseDataColumns;
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
                $this->fileStorage->saveToCSVFile($path, $dataRows, $columnNames[0]);
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
     * @param $endpoint
     * @param $username
     * @param $password
     * @param PartnerParamInterface $params
     * @return mixed
     * @throws LoginFailException
     */
    function getLogin($endpoint, $username, $password, PartnerParamInterface $params):string
    {
        $authToken = false;
        $data = array(
            'email' => $username,
            'password' => $password
        );

        $request = $this->curl->newJsonRequest('post', self::PARAM_BASE_URL . $endpoint , $data);
        $response = $this->curl->sendRequest($request);

        $this->checkStatus($params, $response->statusCode);

        $authToken = \GuzzleHttp\json_decode($response->body)->token;

        return $authToken;
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
        $request = $this->curl->newJsonRequest('post', self::PARAM_BASE_URL . $url, $data);
        $request->setHeader("Authorization", $accessToken);
        $response = $this->curl->sendRequest($request);

        $this->checkStatus($params, $response->statusCode);

        $report = \GuzzleHttp\json_decode($response->body, true);

        $headLine = [];
        $dataLine = [];

        foreach ($report as $key => $item) {
            if ($key == 0) $headLine[] = array_keys($item);
            $dataLine[] = $item;
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