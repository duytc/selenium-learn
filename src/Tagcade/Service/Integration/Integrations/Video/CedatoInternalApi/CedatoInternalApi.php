<?php

namespace Tagcade\Service\Integration\Integrations\Video\CedatoInternalApi;

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

class CedatoInternalApi extends IntegrationAbstract implements IntegrationInterface
{
    /*
     * Command to create:
     * php app/console ur:integration:create video-cedato-internal-api "Cedato-Internal" -p "username,password:secure,dateRange:dynamicDateRange" -a -vv
     */
    const INTEGRATION_C_NAME = 'video-cedato-internal-api';

    /* params from integration */
    const PARAM_BASE_URL = 'https://api.cedato.com/api'; // url for getting session id
    const PARAM_ENDPOINT_SESSION = '/token';
    const PARAM_ENDPOINT_DEMAND = '/reports/demands/basic';
    const PARAM_ENDPOINT_SUPPLY = '/reports/supplies/basic';

    const PARAM_ACCESS_KEY = 'accessKey';
    const PARAM_SECRET_KEY = 'secretKey';

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

                $responseData = $this->getReport($sessionKey, $params, $startDate->getTimestamp(), $endDate->getTimestamp());


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
     */
    function getLogin($endpoint, $username, $password, PartnerParamInterface $params): string
    {
        $data = "grant_type=client_credentials";

        $auth = base64_encode($username . ':' . $password);

        $request = $this->curl->newRawRequest('post', self::PARAM_BASE_URL.$endpoint, $data);
        $request->setHeader('Content-Type', "application/x-www-form-urlencoded");
        $request->setHeader('Accept', "application/json");
        $request->setHeader('Api-Version', "1");
        $request->setHeader('Authorization', "Basic $auth");

        $response = $this->curl->sendRequest($request);

        $this->checkStatus($params, $response->statusCode, 'login', 200);

        $body = \GuzzleHttp\json_decode($response->body);
        $authToken = $body->data->access_token;

        return $authToken;
    }

    /**
     * @param $accessToken
     * @param PartnerParamInterface $params
     * @param $start
     * @param $end
     * @return array
     */
    function getReport($accessToken, PartnerParamInterface $params, $start, $end): array
    {
        $this->logger->info("Making Report Request");

        $url = self::PARAM_BASE_URL . self::PARAM_ENDPOINT_DEMAND . '?start=' . $start . '&end=' . $end;

        $rawData = $this->setReportRequest($accessToken, $url, $params);

        $data = $rawData["data"]["demands"];

        while ($rawData["data"]["navigation"]["next"]) {
            $url = $rawData["data"]["navigation"]["next"];
            $rawData = $this->setReportRequest($accessToken, $url, $params);
            array_merge($data, $rawData["data"]["demands"]);
        }
        return $data;
    }

    protected function setReportRequest(string $accessToken, string $url, PartnerParamInterface $params) {
        $request = $this->curl->newRawRequest('get', $url);
        $request->setHeader('Accept', "application/json");
        $request->setHeader('Api-Version', "1");
        $request->setHeader('Authorization', "Bearer $accessToken");
        $response = $this->curl->sendRequest($request);

        $this->checkStatus($params, $response->statusCode, 'getting report');

        $rawData = \GuzzleHttp\json_decode($response->body, true);

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