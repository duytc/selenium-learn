<?php

namespace Tagcade\Service\Integration\Integrations\RedshiftVideo\RedshiftAggregatedVideo;

use DateInterval;
use DatePeriod;
use DateTime;
use Exception;
use PDO;
use Psr\Log\LoggerInterface;
use Redis;
use Tagcade\Exception\LoginFailException;
use Tagcade\Exception\RuntimeException;
use Tagcade\Service\Core\TagcadeRestClientInterface;
use Tagcade\Service\DownloadFileHelper;
use Tagcade\Service\Fetcher\Params\PartnerParams;
use Tagcade\Service\FileStorageServiceInterface;
use Tagcade\Service\Integration\Config;
use Tagcade\Service\Integration\ConfigInterface;
use Tagcade\Service\Integration\Integrations\IntegrationAbstract;
use Tagcade\Service\Integration\Integrations\IntegrationInterface;

class RedshiftAggregatedVideo extends IntegrationAbstract implements IntegrationInterface
{
    /*
     * Command to create:
     * php app/console ur:integration:create redshift-aggregated-video "Aggregated Video Report" -a -p dateRange:dynamicDateRange -vv
     */

    const INTEGRATION_C_NAME = 'redshift-aggregated-video';

    const PARAM_START_DATE = 'startDate';
    const PARAM_END_DATE = 'endDate';
    const PARAM_DATE_RANGE = 'dateRange';
    const PARAM_PATTERN = 'pattern';

    const CSV_CONTENT_TYPE = 'text/csv';
    const JSON_CONTENT_TYPE = 'application/json';

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var DownloadFileHelper
     */
    private $downloadFileHelper;

    /**
     * @var FileStorageServiceInterface
     */
    protected $fileStorage;

    /** @var TagcadeRestClientInterface */
    protected $restClient;
    /**
     * @var PDO
     */
    protected $redshift;

    protected $redis;


    /**
     * GeneralIntegrationAbstract constructor.
     * @param LoggerInterface $logger
     * @param DownloadFileHelper $downloadFileHelper
     * @param FileStorageServiceInterface $fileStorage
     * @param TagcadeRestClientInterface $restClient
     * @param PDO $redshift
     * @param Redis $redis
     */
    public function __construct(
        LoggerInterface $logger,
        DownloadFileHelper $downloadFileHelper,
        FileStorageServiceInterface $fileStorage,
        TagcadeRestClientInterface $restClient,
        PDO $redshift,
        Redis $redis
    )
    {
        $this->logger = $logger;
        $this->downloadFileHelper = $downloadFileHelper;
        $this->fileStorage = $fileStorage;
        $this->restClient = $restClient;
        $this->redshift = $redshift;
        $this->redis = $redis;
    }

    /**
     * @inheritdoc
     */
    public function run(ConfigInterface $config)
    {
        $params = new PartnerParams($config);

        $this->redshift->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->redshift->exec("SET TIMEZONE = 'PST8PDT'");

        $publisherId = $params->getPublisherId();
        $dataSourceId = $params->getDataSourceId();

        $videoDynamicRange = $config->getParamValue(self::PARAM_DATE_RANGE, null);
        // will get these directly from config in this case
        list($startDateStr, $endDateStr) = Config::extractDynamicDateRange($videoDynamicRange);
        $startDate = new DateTime($startDateStr);
        $endDate = new DateTime($endDateStr);

        // if start date is today, update redis to add datasource it to the etl set
        if ($startDate >= new DateTime('today')) {
            $this->redis->setex('dataSourcesForDailyVpaid' . $dataSourceId, 604800, 'value');
            die("Updated Redis Key with data source id $dataSourceId.\n");
        }
        $fileName = sprintf(
            '%s_%s_%d%s',
            'file',
            (new DateTime())->getTimestamp(),
            strtotime(date('Y-m-d')),
            $this->downloadFileHelper->getFileExtension(self::JSON_CONTENT_TYPE)
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
            $columnNames = array(
                array(
                    'timestamp_hour',
                    'publisher_id',
                    'process_time',
                    'publisher_name',
                    'waterfall_id',
                    'waterfall_name',
                    'platform',
                    'demand_partner',
                    'demand_tag_id',
                    'country',
                    'browser_name',
                    'browser_os',
                    'browser_major',
                    'declared_domain',
                    'detected_domain',
                    'declared_player_size',
                    'detected_player_size',
                    'buy_price',
                    'sell_price',
                    'demand_revenue',
                    'supply_cost',
                    'net_revenue',
                    'requests',
                    'impressions',
                    'responses',
                    'loads',
                    'served',
                    'filled',
                    'timeouts',
                    'errors',
                    'response_time_less_than_1_sec',
                    'response_time_1_to_3_sec',
                    'response_time_3_to_5_sec',
                    'response_time_more_than_5_sec',
                    'error_vpaid',
                    'error_creative',
                    'error_unknown',
                ),
            );
            $dataRows = [];
            $this->logger->debug('Starting download file');


            foreach ($dateRange as $i => $singleDate) {
                if (!$singleDate instanceof DateTime) {
                    continue;
                }

                $responseData = $this->doQueryData(
                    $startDate->format('Y-m-d'),
                    $endDate->format('Y-m-d'),
                    $publisherId
                );

                foreach ($responseData as $responseDatum) {
                    $dataRows[] = $responseDatum;
                }
            }

            $this->logger->debug('Save download file');
//            $this->fileStorage->saveToCSVFile($path, $dataRows, $columnNames);
            $f = fopen($path, 'w');
            $jsonData = \GuzzleHttp\json_encode(array($columnNames, $dataRows));
            fwrite($f, $jsonData);

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
     * @param string $startDate
     * @param string $endDate
     * @param int $publisherId
     * @return array of 2 elements, first element is the response data, second is the content type
     * @throws Exception
     */
    protected function doQueryData(string $startDate, string $endDate, int $publisherId): array
    {
        $query = "
                  SELECT *
                  FROM log_pixel_vpaid_aggregated
                  WHERE publisher_id = %d AND timestamp_hour >= '%s' AND timestamp_hour < '%s'
                  ;";

        $query = sprintf($query, $publisherId, $startDate, $endDate);

        $this->logger->info("query database");

        $result = $this->redshift->query($query);

        $this->logger->info("finishing querying");

        $responseData = $result->fetchAll(PDO::FETCH_ASSOC);

        return $responseData;
    }
}