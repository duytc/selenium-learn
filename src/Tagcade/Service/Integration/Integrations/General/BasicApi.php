<?php

namespace Tagcade\Service\Integration\Integrations\General;

use DateTime;
use Exception;
use Psr\Log\LoggerInterface;
use Tagcade\Exception\RuntimeException;
use Tagcade\Service\Core\TagcadeRestClientInterface;
use Tagcade\Service\Fetcher\Params\PartnerParams;
use Tagcade\Service\FileStorageServiceInterface;
use Tagcade\Service\Integration\Config;
use Tagcade\Service\Integration\ConfigInterface;
use Tagcade\Service\Integration\Integrations\IntegrationAbstract;
use Tagcade\Service\Integration\Integrations\IntegrationInterface;

class BasicApi extends IntegrationAbstract implements IntegrationInterface
{
    /*
     * Command to create:
     * php app/console ur:integration:create general-basic-api "Basic Api" -a -p apiUrl,dateFormat,dateRange:dynamicDateRange -vv
     */

    const INTEGRATION_C_NAME = 'general-basic-api';

    /* params from integration */
    const PARAM_API_URL = 'apiUrl'; // url for getting reports
    const PARAM_DATE_FORMAT = 'dateFormat';
    const PARAM_START_DATE = 'startDate';
    const PARAM_END_DATE = 'endDate';
    const PARAM_DATE_RANGE = 'dateRange';
    const PARAM_PATTERN = 'pattern';

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

    /** @var TagcadeRestClientInterface */
    protected $restClient;

    /**
     * GeneralIntegrationAbstract constructor.
     * @param LoggerInterface $logger
     * @param FileStorageServiceInterface $fileStorage
     * @param TagcadeRestClientInterface $restClient
     */
    public function __construct(LoggerInterface $logger, FileStorageServiceInterface $fileStorage, TagcadeRestClientInterface $restClient)
    {
        $this->logger = $logger;
        $this->fileStorage = $fileStorage;
        $this->restClient = $restClient;
    }

    /**
     * @inheritdoc
     */
    public function run(ConfigInterface $config)
    {
        // todo fixed share state problem, a new object should be created for each config

        // I have created this integration to use no shared state
        $filePattern = $config->getParamValue(self::PARAM_PATTERN, null);
        $startDateEndDate = $config->getStartDateEndDate();

        $startDate = $startDateEndDate[Config::PARAM_START_DATE];

        if (!$startDate instanceof DateTime) {
            throw new Exception('The startDate must be a DateTime');
        }

        $endDate = $startDateEndDate[Config::PARAM_END_DATE];

        if (!$endDate instanceof DateTime) {
            throw new Exception('The endDate must be a DateTime');
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

        // create metadata file.
        // metadata file contains file pattern, so it lets directory monitory has information to get exact data source relates to file pattern
        $metadata = [
            'module' => 'integration',
            'publisherId' => $config->getPublisherId(),
            'dataSourceId' => $config->getDataSourceId(),
            'integrationCName' => $config->getIntegrationCName(),
            'pattern' => $filePattern,
            'uuid' => bin2hex(random_bytes(15)) // make all metadata files have difference hash values when being processed in directory monitor
        ];
        $metadataFilePath = $path . '.meta';
        file_put_contents($metadataFilePath, json_encode($metadata));

        $this->restClient->updateIntegrationWhenDownloadSuccess(new PartnerParams($config));
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
            // will be retry
            throw new RuntimeException(sprintf('Cannot get data from this url, errorCode = %s', $statusCode));
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
        $contentType = preg_replace('/;.*/', '', $contentType);

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