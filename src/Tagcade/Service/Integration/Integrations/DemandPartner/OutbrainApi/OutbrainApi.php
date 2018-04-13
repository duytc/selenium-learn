<?php

namespace Tagcade\Service\Integration\Integrations\DemandPartner\OutbrainApi;

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

class OutbrainApi extends IntegrationAbstract implements IntegrationInterface
{
    /*
     * Command to create:
     * php app/console ur:integration:create outbrain-api "Outbrain API" -a -p username:plainText,password:secure -vv
     */

    const INTEGRATION_C_NAME = 'outbrain-api';

    /* params from integration */
    const PARAM_BASE_URL = 'https://api.outbrain.com/engage/v1/';
    const PARAM_ENDPOINT_LOGIN = 'login';
    const PARAM_ENDPOINT_REPORTS = 'reports/outbrain/publishers';
    const PARAM_ENDPOINT_PUBLISHERS = 'lookups/publishers';

    const PARAM_USERNAME = 'username';
    const PARAM_PASSWORD = 'password';
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

        $sessionKey = $this->getLogin(self::PARAM_ENDPOINT_LOGIN, $username, $password, $params);

        $publishers = $this->getPublishers($sessionKey, $params);

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

                list($responseData, $responseNames) = $this->getReport(
                    $sessionKey,
                    $params,
                    $startDate->format('Ymd'),
                    $endDate->format('Ymd'),
                    $publishers
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
     * @param $endpoint
     * @param $username
     * @param $password
     * @param PartnerParamInterface $params
     * @return mixed
     * @throws LoginFailException
     */
    function getLogin($endpoint, $username, $password, PartnerParamInterface $params): string
    {
        $this->logger->info("Getting auth token");

        $authHeader = "Basic " . base64_encode("$username:$password");

        $request = $this->curl->newRawRequest('get', self::PARAM_BASE_URL.$endpoint);
        $request->setHeader('Authorization', $authHeader);

        $response = $this->curl->sendRequest($request);

        $this->checkStatus($params, $response->statusCode, 'login', 200);

        $authToken = \GuzzleHttp\json_decode($response->body, true)["OB-TOKEN-V1"];

        return $authToken;
    }

    /**
     * @param $accessToken
     * @param PartnerParamInterface $params
     * @param $startDate
     * @param $endDate
     * @return array
     */
    function getReport($accessToken, PartnerParamInterface $params, $startDate, $endDate, $publishers): array
    {
        $this->logger->info("Making GET request for report");

        $data = [];

        foreach ($publishers as $publisher) {

            $url = self::PARAM_BASE_URL.self::PARAM_ENDPOINT_REPORTS.'/'. $publisher["id"] . '?filter=&fromDate='.$startDate.'&toDate='.$endDate;

            $request = $this->curl->newRawRequest('get', $url);
            $request->setHeader("OB-TOKEN-V1", $accessToken);
            $response = $this->curl->sendRequest($request);

            $this->checkStatus($params, $response->statusCode, 'getting report');

            $rawData = \GuzzleHttp\json_decode($response->body, true)["items"];

            foreach ($rawData as $rawDatum) {
                $outputArray = [];
                $outputArray['Publisher'] = $publisher["name"];
                foreach ($rawDatum as $key => $item)
                {
                    $outputArray[$key] = $item["value"];
                }
                array_push($data, $outputArray);
            }
            $names = array_keys($rawData[0]);
            array_unshift($names, "Publisher");
        }

        return [$data, $names];
    }

    /**
     * @param $accessToken
     * @param PartnerParamInterface $params
     * @return array
     */
    function getPublishers($accessToken, PartnerParamInterface $params): array
    {
        $this->logger->info("Making GET request for list of publishers");

        $url = self::PARAM_BASE_URL.self::PARAM_ENDPOINT_PUBLISHERS;

        $request = $this->curl->newRawRequest('get', $url);
        $request->setHeader("OB-TOKEN-V1", $accessToken);
        $response = $this->curl->sendRequest($request);

        $this->checkStatus($params, $response->statusCode, 'getting pubishers');

        $rawData = \GuzzleHttp\json_decode($response->body, true)["publishers"];

        return $rawData;
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