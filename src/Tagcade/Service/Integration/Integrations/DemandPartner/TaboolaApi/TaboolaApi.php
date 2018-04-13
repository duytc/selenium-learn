<?php

namespace Tagcade\Service\Integration\Integrations\DemandPartner\TaboolaApi;

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

class TaboolaApi extends IntegrationAbstract implements IntegrationInterface
{
    /*
     * Command to create:
     * php app/console ur:integration:create taboola-api "Taboola API" -a -p "clientId,secretId:secure,accountName,dateRange:dynamicDateRange,dimensions:option:Day;Week;Month,reportType:option:Revenue Summary" -vv
     */

    const INTEGRATION_C_NAME = 'taboola-api';

    /* params from integration */
    const PARAM_BASE_URL = 'https://backstage.taboola.com/';
    const PARAM_ENDPOINT_LOGIN = 'backstage/oauth/token';
    const PARAM_ENDPOINT_REPORTS = 'backstage/api/1.0/%s/reports/%s/dimensions/%s';


    const PARAM_CLIENT_ID = 'clientId';
    const PARAM_SECRET_ID = 'secretId';
    const PARAM_ACCOUNT = 'accountName';
    const PARAM_REPORT_TYPE = 'reportType';

    const PARAM_DIMENSIONS = 'dimensions';

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

        $userParams["username"] = $config->getParamValue(self::PARAM_CLIENT_ID, null);
        $userParams["password"] = $config->getParamValue(self::PARAM_SECRET_ID, null);
        $userParams["account"] = $config->getParamValue(self::PARAM_ACCOUNT, null);
        $userParams["dimensions"] = strtolower($config->getParamValue(self::PARAM_DIMENSIONS, null));
        $userParams["reportType"] = strtolower(implode("-", explode( ' ', $config->getParamValue(self::PARAM_REPORT_TYPE, null))));

        $userParams["sessionKey"] = $this->getLogin($userParams["username"], $userParams["password"], $params);

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

                list($responseData, $responseNames) = $this->getReport(
                    $userParams,
                    $params,
                    $startDate->format('Y-m-d'),
                    $endDate->format('Y-m-d')
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
     * @param $username
     * @param $password
     * @param PartnerParamInterface $params
     * @return mixed
     */
    function getLogin($username, $password, PartnerParamInterface $params): string
    {
        $this->logger->info("Getting auth token");

        $body = "?client_id=$username&client_secret=$password&grant_type=client_credentials";

        $request = $this->curl->newRawRequest('post', self::PARAM_BASE_URL . self::PARAM_ENDPOINT_LOGIN . $body);
        $request->setHeader('Content-Type', 'application/x-www-form-urlencoded');

        $response = $this->curl->sendRequest($request);

        $this->checkStatus($params, $response->statusCode, 'login', 200);

        $authToken = \GuzzleHttp\json_decode($response->body, true)["access_token"];

        return $authToken;
    }

    /**
     * @param array $userParams
     * @param PartnerParamInterface $params
     * @param $startDate
     * @param $endDate
     * @return array
     */
    function getReport(array $userParams, PartnerParamInterface $params, $startDate, $endDate): array
    {
        $this->logger->info("Making GET request for report");

        $url = self::PARAM_BASE_URL .
            sprintf(
                self::PARAM_ENDPOINT_REPORTS,
                $userParams["account"],
                $userParams["reportType"],
                $userParams["dimensions"]
                ) . "?start_date=$startDate&end_date=$endDate";

        $request = $this->curl->newRawRequest('get', $url);
        $request->setHeader("Authorization", "Bearer " . $userParams["sessionKey"]);
        $response = $this->curl->sendRequest($request);

        $this->checkStatus($params, $response->statusCode, 'getting report');

        $data = \GuzzleHttp\json_decode($response->body, true)["results"];

        $names = array_keys($data[0]);

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