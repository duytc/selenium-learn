<?php

namespace Tagcade\Service\Integration\Integrations\DemandPartner\MediaDotNetApi;

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

class MediaDotNetApi extends IntegrationAbstract implements IntegrationInterface
{
    /*
     * Command to create:
     * php app/console ur:integration:create demand-partner-mediadotnetapi "Media.net API" -a -p customerKey,customerGuid:secure,dateRange:dynamicDateRange -vv
     */

    const INTEGRATION_C_NAME = 'demand-partner-mediadotnetapi';

    /* params from integration */
    const PARAM_API_URL = 'https://control.media.net/api/reports/datewise?customer_key=%s&customer_guid=%s&from_date=%s&to_date=%s'; // url for getting reports

    const PARAM_CUSTOMER_KEY = 'customerKey';
    const PARAM_CUSTOMER_GUID = 'customerGuid';

    const URL_CUSTOMER_KEY = 'customerKey';
    const URL_CUSTOMER_GUID = 'customerGuid';
    const URL_DATE = 'Date';
    const URL_IMPRESSIONS = 'Impressions';
    const URL_REVENUE = 'Revenue';
    const URL_RPM = 'RPM';
    const URL_VIEWABLE_IMPRESSION = 'Viewable Impression';
    const URL_VIEWABLE_IMPRESSION_PERCENT = 'Viewable Impression Percent';
    const URL_VIEWABLE_RPM = 'Viewable RPM';

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

        $customerKey = $config->getParamValue(self::URL_CUSTOMER_KEY, null);
        $customerGuid = $config->getParamValue(self::URL_CUSTOMER_GUID, null);
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

                $apiUrl = sprintf(
                    self::PARAM_API_URL,
                    $customerKey,
                    $customerGuid,
                    $startDate->format("m/d/Y"),
                    $endDate->format("m/d/Y")
                );

                // in php 7.1 you can use [responseData, $contentType] = $this->doGetData($apiUrl);
                list($responseData, $responseDataColumns) = $this->doGetData($apiUrl, $params);

                $dataRows[] = $responseData;
                if ($countHead == 0) {
                    $columnNames[] = $responseDataColumns;
                }
                $this->logger->debug('Save download file');

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
                $dataRows = [];
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
                throw new RuntimeException(sprintf('Cannot get data from this url, errorCode = %s', $response->statusCode));
            }
        }
        $body = simplexml_load_string($response->toArray()[self::RESPONSE_BODY]);

        $bodyArr = \GuzzleHttp\json_decode(\GuzzleHttp\json_encode($body));
        $returnArr = array(
            self::URL_DATE => isset($bodyArr->statsData->reportItem->{self::RESPONSE_ATTRIBUTES}->date) ? $bodyArr->statsData->reportItem->{self::RESPONSE_ATTRIBUTES}->date : "",
            self::URL_IMPRESSIONS => isset($bodyArr->statsData->reportItem->{self::RESPONSE_ATTRIBUTES}->impressions) ? $bodyArr->statsData->reportItem->{self::RESPONSE_ATTRIBUTES}->impressions : "",
            self::URL_REVENUE => isset($bodyArr->statsData->reportItem->{self::RESPONSE_ATTRIBUTES}->estimatedRevenue) ? $bodyArr->statsData->reportItem->{self::RESPONSE_ATTRIBUTES}->estimatedRevenue : "",
            self::URL_RPM => isset($bodyArr->statsData->reportItem->{self::RESPONSE_ATTRIBUTES}->rpm) ? $bodyArr->pageTotal->{self::RESPONSE_ATTRIBUTES}->rpm : "",
            self::URL_VIEWABLE_IMPRESSION => isset($bodyArr->statsData->reportItem->{self::RESPONSE_ATTRIBUTES}->viewableImpression) ? $bodyArr->statsData->reportItem->{self::RESPONSE_ATTRIBUTES}->viewableImpression : "",
            self::URL_VIEWABLE_IMPRESSION_PERCENT => isset($bodyArr->statsData->reportItem->{self::RESPONSE_ATTRIBUTES}->viewableImpressionPercent) ? $bodyArr->statsData->reportItem->{self::RESPONSE_ATTRIBUTES}->viewableImpressionPercent : "",
            self::URL_VIEWABLE_RPM => isset($bodyArr->statsData->reportItem->{self::RESPONSE_ATTRIBUTES}->viewableRpm) ? $bodyArr->statsData->reportItem->{self::RESPONSE_ATTRIBUTES}->viewableRpm : "",
        );

        $headLine = [];
        $dataLine = [];
        foreach ($returnArr as $key => $item) {
            $headLine[] = $key;
            $dataLine[] = $item;
        }

        return [$dataLine, $headLine];
    }
}