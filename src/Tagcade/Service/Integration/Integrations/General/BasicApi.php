<?php

namespace Tagcade\Service\Integration\Integrations\General;

use DateTime;
use Exception;
use Psr\Log\LoggerInterface;
use Tagcade\Service\FileStorageServiceInterface;
use Tagcade\Service\Integration\Config;
use Tagcade\Service\Integration\ConfigInterface;
use Tagcade\Service\Integration\Integrations\IntegrationAbstract;
use Tagcade\Service\Integration\Integrations\IntegrationInterface;

class BasicApi extends IntegrationAbstract implements IntegrationInterface
{
    /* Command to create:
    php app/console ur:integration:create "Basic Api" general-basic-api -a -p apiUrl,dateFormat,dateRange:dynamicDateRange -vv
    */

    const INTEGRATION_C_NAME = 'general-basic-api';

    /* params from integration */
    const PARAM_API_URL = 'apiUrl'; // url for getting reports
    const PARAM_DATE_FORMAT = 'dateFormat';
    const PARAM_START_DATE = 'startDate';
    const PARAM_END_DATE = 'endDate';
    const PARAM_DATE_RANGE = 'dateRange';

    /* macros for replace values of url */
    const MACRO_START_DATE = '${start_date}';
    const MACRO_END_DATE = '${end_date}';

    const XLS_CONTENT_TYPE = 'application/vnd.ms-excel';
    const XLSX_CONTENT_TYPE = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
    const XML_CONTENT_TYPE = 'application/xml';
    const JSON_CONTENT_TYPE = 'application/json';
    const CSV_CONTENT_TYPE = 'text/csv';

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var FileStorageServiceInterface
     */
    protected $fileStorage;

    /**
     * GeneralIntegrationAbstract constructor.
     * @param LoggerInterface $logger
     * @param FileStorageServiceInterface $fileStorage
     */
    public function __construct(LoggerInterface $logger, FileStorageServiceInterface $fileStorage)
    {
        $this->logger = $logger;
        $this->fileStorage = $fileStorage;
    }

    /**
     * @inheritdoc
     */
    public function run(ConfigInterface $config)
    {
        // todo fixed share state problem, a new object should be created for each config

        // I have created this integration to use no shared state

        $startDateEndDate = $config->getStartDateEndDate();

        $startDate = $startDateEndDate[Config::PARAM_START_DATE];

        if (!$startDate instanceof DateTime) {
            throw new Exception('startDate must be a DateTime');
        }

        $endDate = $startDateEndDate[Config::PARAM_END_DATE];

        if (!$endDate instanceof DateTime) {
            throw new Exception('endDate must be a DateTime');
        }

        $apiUrl = $config->getParamValue(self::PARAM_API_URL, null);
        $dateFormat = $config->getParamValue(self::PARAM_DATE_FORMAT, 'Y-m-d');

        $startDateString = $startDate->format($dateFormat);
        $endDateString = $endDate->format($dateFormat);

        if (!$startDateString || !$endDateString) {
            throw new Exception(sprintf('The date format "%s" is invalid', $dateFormat));
        }

        // TODO: handle dailyBreakdown if need...

        $apiUrl = str_replace(self::MACRO_START_DATE, $startDateString, $apiUrl);
        $apiUrl = str_replace(self::MACRO_END_DATE, $endDateString, $apiUrl);

        // in php 7.1 you can use [responseData, $contentType] = $this->doGetData($apiUrl);
        list($responseData, $contentType) = $this->doGetData($apiUrl);

        $fileName = sprintf('%s_%d%s', 'file', strtotime(date('Y-m-d')), $this->getFileExtension($contentType));

        // important: each file will be stored in separated dir,
        // then metadata is stored in same this dir
        // so that we know file and metadata file is in pair
        $subDir = sprintf('%s-%s', $fileName, (new DateTime())->getTimestamp());

        $path = $this->fileStorage->getDownloadPath($config, $fileName, $subDir);

        file_put_contents($path, $responseData);
    }

    /**
     * @param $apiUrl
     * @return array of 2 elements, first element is the response data, second is the content type
     * @throws Exception
     */
    protected function doGetData($apiUrl): array
    {
        $curl = curl_init();

        curl_setopt($curl, CURLOPT_URL, $apiUrl);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        $responseData = curl_exec($curl);

        $statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        if ($statusCode !== 200) {
            throw new Exception(sprintf('cannot get data from this url, errorCode= %s', $statusCode));
        }

        $contentType = curl_getinfo($curl, CURLINFO_CONTENT_TYPE);

        // close curl
        if (null !== $curl) {
            curl_close($curl);
            $curl = null;
        }

        return [$responseData, $contentType];
    }

    /**
     * @param string $contentType
     * @return string
     */
    protected function getFileExtension($contentType)
    {
        // todo, this could be in a service for reuse

        switch ($contentType) {
            case self::XLSX_CONTENT_TYPE:
                $fileType = '.xlsx';
                break;
            case self::XLS_CONTENT_TYPE:
                $fileType = '.xls';
                break;
            case self::XML_CONTENT_TYPE:
                $fileType = '.xml';
                break;
            case self::JSON_CONTENT_TYPE:
                $fileType = '.json';
                break;
            case self::CSV_CONTENT_TYPE:
                $fileType = '.csv';
                break;
            default:
                $fileType = '.txt';
        }

        return $fileType;
    }
}