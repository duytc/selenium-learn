<?php

namespace Tagcade\Service\Integration\Integrations\DemandPartner\AdvertiseDotComApi;

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

class AdvertiseDotComApi extends IntegrationAbstract implements IntegrationInterface
{
    /*
     * Command to create:
     * php app/console ur:integration:create demand-partner-advertisedotcomapi "Advertise.com API" -a -p username,password:secure,dateRange:dynamicDateRange -vv
     */

    const INTEGRATION_C_NAME = 'demand-partner-advertisedotcomapi';

    /* params from integration */
    const PARAM_API_BASE_URL = 'https://api01.advertise.com/ads-wsapi/api/%s?user=%s&password=%s'; // url for getting reports (api call, username, password
    const USERNAME = 'username';
    const PASSWORD = 'password';
    const CAMPAIGN_IDS = 'campaignIds';

    const RESPONSE_ATTRIBUTES = '@attributes';
    const RESPONSE_BODY = 'body';
    const CSV_CONTENT_TYPE = 'text/csv';

    const URL_CAMPAIGN_NAME = 'Campaign Name';
    const URL_DATE = 'Date';
    const URL_BILLED_CLICKED = 'Billed Clicks';
    const URL_UN_BILLED_CLICKED = 'Unbilled Clicks';
    const URL_TOTAL_CLICKS = 'Total Clicks';
    const URL_AVERAGE_CPC = 'AverageCPC';
    const URL_SPENT = 'Spent';
    const URL_CONVERSION_RATE = 'Conversion Rate';
    const URL_TOTAL_CONVERSION = 'Total Conversion';
    const URL_COST_TO_CONVERSION = 'Cost To Conversion';

    const SCHEDULE_CAMPAIGN_PERFORMANCE_REPORT = 'schedulecampaignperformancereport';
    const GET_REPORT = 'getreport';
    const SCHEDULE_REPORT_ID = 'scheduledreportid';

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

        $username = $config->getParamValue(self::USERNAME, null);
        $password = $config->getParamValue(self::PASSWORD, null);
        $startDate = $params->getStartDate();
        $endDate = $params->getEndDate();
        $campaignIds = $config->getParamValue(self::CAMPAIGN_IDS, 'all');
        $fileName = sprintf('%s_%s_%d%s',
            'file',
            (new DateTime())->getTimestamp(),
            strtotime(date('Y-m-d')),
            $this->downloadFileHelper->getFileExtension(self::CSV_CONTENT_TYPE));

        $subDir = sprintf('%s-%s', $startDate->format("Ymd"), $endDate->format("Ymd"));
        $downloadFolderPath = $this->fileStorage->getDownloadPath($config, '', $subDir);
        $path = $this->fileStorage->getDownloadPath($config, $fileName, $subDir);

        $endDate = $endDate->modify('+1 day'); // add 1 day for DateInterval correctly loop from startDate to endDate
        $interval = new DateInterval('P1D');
        $dateRange = new DatePeriod($startDate, $interval, $endDate);

        try {
            $columns = [];
            $rows = [];
            $rowCount = 0;

            $this->logger->debug('Starting download file');
            foreach ($dateRange as $i => $singleDate) {
                if (!$singleDate instanceof DateTime) {
                    continue;
                }

                $startDate = clone $singleDate;
                $endDate = $singleDate->modify("+1d");

                /** Get report id */
                $apiUrl = $this->buildAPIURL($username, $password);
                $scheduleUrl = $this->buildScheduleURL($apiUrl, $startDate, $endDate, $campaignIds);
                $reportId = $this->doGetReportId($scheduleUrl, $params);

                /** Build URL and get data */
                $getReportUrl = $this->buildReportUrl($username, $password, $reportId);
                list($row, $column) = $this->doGetData($getReportUrl, $params);

                $rows[] = $row;
                if ($rowCount == 0) {
                    $columns[] = $column;
                }

                $rowCount++;
            }

            if (empty($columns) || empty($rows)) {
                throw new RuntimeException('Can not get report');
            }

            $this->logger->debug('Save download file');
            $this->fileStorage->saveToCSVFile($path, $rows, $columns);

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
     * @param string $apiUrl
     * @param PartnerParamInterface $params
     * @return string
     * @throws LoginFailException
     */
    protected function doGetReportId(string $apiUrl, PartnerParamInterface $params): string
    {
        $request = $this->curl->newRawRequest('get', $apiUrl);
        $response = $this->curl->sendRequest($request);

        if ($response->statusCode !== 200) {
            // will be retry
            if ($response->statusCode >= 400 && $response->statusCode < 500) {
                throw new LoginFailException(
                    $params->getPublisherId(),
                    $params->getIntegrationCName(),
                    $params->getDataSourceId(),
                    $params->getStartDate(),
                    $params->getEndDate(),
                    new \DateTime()
                );
            } else {
                throw new RuntimeException(
                    sprintf('Cannot get data from this url, errorCode = %s', $response->statusCode)
                );
            }
        }
        $body = simplexml_load_string($response->toArray()[self::RESPONSE_BODY]);

        $bodyArr = \GuzzleHttp\json_decode(\GuzzleHttp\json_encode($body));

        $reportId = isset($bodyArr->elements->entity->contents->scheduleCampaignPerformanceReportResponse->scheduledReports->scheduledReportDetails->scheduledReportId)
            ? $bodyArr->elements->entity->contents->scheduleCampaignPerformanceReportResponse->scheduledReports->scheduledReportDetails->scheduledReportId : '';

        if (!$reportId) {
            throw new LoginFailException(
                $params->getPublisherId(),
                $params->getIntegrationCName(),
                $params->getDataSourceId(),
                $params->getStartDate(),
                $params->getEndDate(),
                new \DateTime()
            );
        }
        return $reportId;
    }

    /**
     * @param string $apiUrl
     * @param PartnerParamInterface $params
     * @return array of 2 elements, first element is the response data, second is the content type
     * @throws LoginFailException
     */
    protected function doGetData(string $apiUrl, PartnerParamInterface $params): array
    {
        $request = $this->curl->newRawRequest('get', $apiUrl);
        $response = $this->curl->sendRequest($request);

        if ($response->statusCode !== 200) {
            // will be retry
            if ($response->statusCode >= 400 && $response->statusCode < 500) {
                throw new LoginFailException(
                    $params->getPublisherId(),
                    $params->getIntegrationCName(),
                    $params->getDataSourceId(),
                    $params->getStartDate(),
                    $params->getEndDate(),
                    new \DateTime()
                );
            } else {
                throw new RuntimeException(
                    sprintf('Cannot get data from this url, errorCode = %s', $response->statusCode)
                );
            }
        }

        $body = simplexml_load_string($response->toArray()[self::RESPONSE_BODY]);
        $bodyArr = \GuzzleHttp\json_decode(\GuzzleHttp\json_encode($body));
        $dataArr = isset($bodyArr->elements->entity->contents->getCampaignPerformanceReportResponse->campaignPerformanceReport)
            ? $bodyArr->elements->entity->contents->getCampaignPerformanceReportResponse->campaignPerformanceReport
            : [];

        if (empty($dataArr)) {
            return [[[]], []];
        }

        $returnArr = array(
            self::URL_DATE => isset($dataArr->date) ? $dataArr->date : "",
            self::URL_CAMPAIGN_NAME => isset($dataArr->campaignname) ? $dataArr->campaignname : "",
            self::URL_BILLED_CLICKED => isset($dataArr->billedClicks) ? $dataArr->billedClicks : "",
            self::URL_UN_BILLED_CLICKED => isset($dataArr->unbilledClicks) ? $dataArr->unbilledClicks : "",
            self::URL_TOTAL_CLICKS => isset($dataArr->totalClicks) ? $dataArr->totalClicks : "",
            self::URL_AVERAGE_CPC => isset($dataArr->averageCPC) ? $dataArr->averageCPC : "",
            self::URL_SPENT => isset($dataArr->spent) ? $dataArr->spent : "",
            self::URL_CONVERSION_RATE => isset($dataArr->conversionRate) ? $dataArr->conversionRate : "",
            self::URL_TOTAL_CONVERSION => isset($dataArr->totalConversion) ? $dataArr->totalConversion : "",
            self::URL_COST_TO_CONVERSION => isset($dataArr->costToConversion) ? $dataArr->costToConversion : "",
        );

        $headLine = [];
        $dataLine = [];

        foreach ($returnArr as $key => $item) {
            $headLine[] = $key;
            $dataLine[] = $item;
        }

        return [$dataLine, $headLine];
    }

    /**
     * @param $username
     * @param $password
     * @return string
     */
    private function buildAPIURL($username, $password)
    {
        return sprintf(self::PARAM_API_BASE_URL, self::SCHEDULE_CAMPAIGN_PERFORMANCE_REPORT, $username, $password);
    }

    /**
     * @param $apiUrl
     * @param DateTime $startDate
     * @param DateTime $endDate
     * @param $campaignIds
     * @return string
     */
    private function buildScheduleURL($apiUrl, DateTime $startDate, DateTime $endDate, $campaignIds)
    {
        return sprintf('%s&startdate=%s&enddate=%s&campaignids=%s', $apiUrl, $startDate->format("m-d-Y"), $endDate->format("m-d-Y"), $campaignIds);
    }

    /**
     * @param $username
     * @param $password
     * @param $reportId
     * @return string
     */
    private function buildReportUrl($username, $password, $reportId)
    {
        $reportUrl = sprintf(self::PARAM_API_BASE_URL, self::GET_REPORT, $username, $password);
        $reportUrl = sprintf('%s&%s=%s', $reportUrl, self::SCHEDULE_REPORT_ID, $reportId);

        return $reportUrl;
    }
}