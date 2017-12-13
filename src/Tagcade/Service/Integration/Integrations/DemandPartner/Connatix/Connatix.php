<?php

namespace Tagcade\Service\Integration\Integrations\DemandPartner\Connatix;

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

class Connatix extends IntegrationAbstract implements IntegrationInterface
{
    /*
     * Command to create:
     * php app/console ur:integration:create demand-partner-connatix "Connatix API" -a -p reportId,username,password:secure,dateRange:dynamicDateRange -vv
     */

    const INTEGRATION_C_NAME = 'demand-partner-connatix';

    /* params from integration */
    const PARAM_API_URL = 'https://console.connatix.com/api/reports/download/'; // url for getting reports
    const PARAM_API_LOGIN_URL = 'https://console.connatix.com/api/account/login'; // url for loggin in
    const REPORT_ID = 'reportId';

    const VALIDATION_PARAMS_USERNAME = 'Username';
    const VALIDATION_PARAMS_PASSWORD = 'Password';
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
    public function __construct(LoggerInterface $logger, DownloadFileHelper $downloadFileHelper, FileStorageServiceInterface $fileStorage, TagcadeRestClientInterface $restClient, cURL $curl)
    {
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
        $apiUrl = self::PARAM_API_URL;
        $loginUrl = self::PARAM_API_LOGIN_URL;
        $username = $params->getUsername();
        $password = $params->getPassword();

        try {
            $cookie = $this->doLogin($loginUrl, $username, $password, $params);

            //check reportId
            if (!is_numeric($config->getParamValue(self::REPORT_ID, null))) {
                throw new \Exception('reportId must be numeric. Please check reportId parameters.');
            }
            // in php 7.1 you can use [responseData, $contentType] = $this->doGetData($apiUrl);MediaDotNetApi
            list($responseData, $contentType) = $this->doGetData($apiUrl, $cookie, $config->getParamValue(self::REPORT_ID, null), $params);

            list($columnNames, $dataRows) = $this->parseDatesFromCsv($responseData, $params->getStartDate(), $params->getEndDate());

            $fileName = sprintf('%s_%d%s', 'file', strtotime(date('Y-m-d')), $this->downloadFileHelper->getFileExtension($contentType));

            // important: each file will be stored in separated dir,
            // then metadata is stored in same this dir
            // so that we know file and metadata file is in pair
            $subDir = sprintf('%s-%s', $params->getStartDate()->format("Ymd"), $params->getEndDate()->format("Ymd"));
            $downloadFolderPath = $this->fileStorage->getDownloadPath($config, '', $subDir);
            $path = $this->fileStorage->getDownloadPath($config, $fileName, $subDir);
            $this->logger->debug('Save download file');
            $this->fileStorage->saveToCSVFile($path, $dataRows, $columnNames);
            // create metadata file.
            // metadata file contains file pattern, so it lets directory monitory has information to get exact data source relates to file pattern
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
     * @param $loginUrl
     * @param $username
     * @param $password
     * @param PartnerParamInterface $params
     * @return string , response cookie from authorization
     * @throws LoginFailException
     */
    protected function doLogin($loginUrl, $username, $password, PartnerParamInterface $params): string
    {
        $validation = array(
            self::VALIDATION_PARAMS_USERNAME => $username,
            self::VALIDATION_PARAMS_PASSWORD => $password
        );

        $request = $this->curl->newJsonRequest('post', $loginUrl, $validation);
        $response = $this->curl->sendRequest($request);
        if ($response->statusCode !== 200) {
            // will be retry
            if ($response->statusCode >= 400 && $response->statusCode <= 500) {
                throw new LoginFailException(
                    $params->getPublisherId(),
                    $params->getIntegrationCName(),
                    $params->getDataSourceId(),
                    $params->getStartDate(),
                    $params->getEndDate(),
                    new \DateTime()
                );
            } else {
                throw new RuntimeException(sprintf('Cannot get data from this url, errorCode = %s', $response->statusCode));
            }
        }

        $cookie = $response->getHeader("set-cookie")[1];

        return $cookie;
    }

    /**
     * @param string $apiUrl
     * @param string $cookie , returned from login, must be set as header
     * @param int $reportNumber
     * @param PartnerParamInterface $params
     * @return array of 2 elements, first element is the response data, second is the content type
     * @throws LoginFailException
     */
    protected function doGetData(string $apiUrl, string $cookie, int $reportNumber, PartnerParamInterface $params): array
    {
        $request = $this->curl->newJsonRequest('get', $apiUrl . $reportNumber);
        $request->setHeader("Cookie", $cookie);
        $response = $this->curl->sendRequest($request);

        if ($response->statusCode !== 200) {
            // will be retry
            if ($response->statusCode >= 400 && $response->statusCode <= 500) {
                throw new LoginFailException(
                    $params->getPublisherId(),
                    $params->getIntegrationCName(),
                    $params->getDataSourceId(),
                    $params->getStartDate(),
                    $params->getEndDate(),
                    new \DateTime()
                );
            } else {
                throw new RuntimeException(sprintf('Cannot get data from this url, errorCode = %s', $response->statusCode));
            }
        }
        $body = \GuzzleHttp\json_decode($response->body);
        $dataRequest = $this->curl->newRequest('get', $body->Url);
        $dataResponse = $this->curl->sendRequest($dataRequest);
        return [$dataResponse->body, self::CSV_CONTENT_TYPE];
    }

    protected function parseDatesFromCsv(string $csvResponse, DateTime $startDate, DateTime $endDate): array
    {
        $responseArray = array();
        $endDate = $endDate->modify('+1 day'); // add 1 day for DateInterval correctly loop from startDate to endDate
        $interval = new DateInterval('P1D');
        $dateRange = new DatePeriod($startDate, $interval, $endDate);

        // turn range into a searchable array of dates
        $validDates = array();
        foreach ($dateRange as $i => $singleDate) {
            if (!$singleDate instanceof DateTime) {
                continue;
            }
            array_push($validDates, $singleDate->format("Y-m-d"));
        }

        // break down the CSV response
        $csvArray = explode(PHP_EOL, $csvResponse);

        foreach ($csvArray as $item) {
            $itemArray = explode(",", $item);
            if (in_array($itemArray[0], $validDates))
                array_push($responseArray, $itemArray);
        }

        $headers = array(explode(",", $csvArray[0]));

        return [$headers, $responseArray];
    }
}