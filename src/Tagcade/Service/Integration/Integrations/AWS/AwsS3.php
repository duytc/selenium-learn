<?php


namespace Tagcade\Service\Integration\Integrations\AWS;


use Aws\Api\DateTimeResult;
use Aws\Result;
use Aws\S3\Exception\S3Exception;
use Aws\S3\S3Client;
use DateTime;
use Exception;
use Psr\Log\LoggerInterface;
use Tagcade\Exception\RuntimeException;
use Tagcade\Service\Core\TagcadeRestClientInterface;
use Tagcade\Service\Fetcher\Params\PartnerParams;
use Tagcade\Service\FileStorageService;
use Tagcade\Service\Integration\Config;
use Tagcade\Service\Integration\ConfigInterface;
use Tagcade\Service\Integration\Integrations\IntegrationAbstract;
use Tagcade\Service\Integration\Integrations\IntegrationInterface;

class AwsS3 extends IntegrationAbstract implements IntegrationInterface
{
    /*
     * Command to create:
     * php app/console ur:integration:create "Amazon S3" aws-s3 -a -p "bucket,pattern:regex,awsKey:secure,awsSecret:secure,awsRegion,dateRange:dynamicDateRange" -vv
     */

    const INTEGRATION_C_NAME = 'aws-s3';

    const PARAM_BUCKET = 'bucket';
    const PARAM_PATTERN = 'pattern';
    const PARAM_AWS_KEY = 'awsKey';
    const PARAM_AWS_SECRET = 'awsSecret';
    const PARAM_AWS_REGION = 'awsRegion';
    const PARAM_VERSION = 'version';
    const PARAM_START_DATE = 'startDate';
    const PARAM_END_DATE = 'endDate';
    const PARAM_DATE_RANGE = 'dateRange';

    const VALUE_VERSION_DEFAULT = 'latest';

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var FileStorageService
     */
    private $fileStorage;

    /** @var TagcadeRestClientInterface */
    protected $restClient;

    /**
     * AwsS3 constructor.
     * @param LoggerInterface $logger
     * @param FileStorageService $fileStorage
     * @param TagcadeRestClientInterface $restClient
     */
    public function __construct(LoggerInterface $logger, FileStorageService $fileStorage, TagcadeRestClientInterface $restClient)
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
        // get all params
        $bucket = $config->getParamValue(self::PARAM_BUCKET, null);
        $filePattern = $config->getParamValue(self::PARAM_PATTERN, null);
        $awsKey = $config->getParamValue(self::PARAM_AWS_KEY, null);
        $awsSecret = $config->getParamValue(self::PARAM_AWS_SECRET, null);
        $awsRegion = $config->getParamValue(self::PARAM_AWS_REGION, null);

        //// important: try get startDate, endDate by backFill
        if ($config->isNeedRunBackFill()) {
            $startDate = $config->getStartDateFromBackFill();
            $endDate = $config->getEndDateFromBackFill();

            if (!$startDate instanceof DateTime) {
                $this->logger->error('need run backFill but backFillStartDate is invalid');
                throw new Exception('need run backFill but backFillStartDate is invalid');
            }

            if (!$endDate instanceof DateTime) {
                $endDate = new DateTime('yesterday');
            }
        } else {
            // prefer dateRange than startDate - endDate
            $dateRange = $config->getParamValue(self::PARAM_DATE_RANGE, null);
            if (!empty($dateRange)) {
                $startDateEndDate = Config::extractDynamicDateRange($dateRange);

                if (!is_array($startDateEndDate)) {
                    // use default 'yesterday'
                    $startDate = new DateTime('yesterday');
                    $endDate = new DateTime('yesterday');
                } else {
                    $startDate = new DateTime($startDateEndDate[0]);
                    $endDate = new DateTime($startDateEndDate[1]);
                }
            } else {
                // use user modified startDate, endDate
                $startDate = new DateTime($config->getParamValue(self::PARAM_START_DATE, 'yesterday'));
                $endDate = new DateTime($config->getParamValue(self::PARAM_END_DATE, 'yesterday'));
            }
        }

        // validate required params
        // TODO: validate start/end date too
        if (empty($bucket) || empty($filePattern) || empty($awsKey) || empty($awsSecret) || empty($awsRegion)) {
            $this->logger->error('missing parameter values for either bucket or filePattern or awsKey or awsSecret or awsRegion');
            throw new Exception('missing parameter values for either bucket or filePattern or awsKey or awsSecret or awsRegion');
        }

        try {
            // create new S3Client instance
            $s3 = new S3Client([
                'credentials' => [
                    'key' => $awsKey,
                    'secret' => $awsSecret,
                ],
                'region' => $awsRegion,
                'version' => self::VALUE_VERSION_DEFAULT,
            ]);

            // do get files from aws
            $iterator = $s3->getIterator('ListObjects', array('Bucket' => $bucket));

            foreach ($iterator as $object) {
                $fileName = $object['Key'];
                if (!preg_match($filePattern, $fileName, $matches)) {
                    continue;
                }

                /** @var DateTimeResult $lastModified */
                $lastModified = $object['LastModified'];
                if (!$this->isNewFile($startDate, $endDate, $lastModified)) {
                    continue;
                }

                // important: each file will be stored in separated dir,
                // then metadata is stored in same this dir
                // so that we know file and metadata file is in pair
                $subDir = sprintf('%s-%s', $fileName, (new DateTime())->getTimestamp());

                $path = $this->fileStorage->getDownloadPath($config, $fileName, $subDir);

                // download file
                /** @var Result $result */
                $result = $s3->getObject(array(
                    'Bucket' => $bucket,
                    'Key' => $fileName,
                    'SaveAs' => $path
                ));

                // check result
                if (!is_array($result['@metadata'])) {
                    $this->logger->warning(sprintf('Can not verify downloading of file %s because missing @metadata from AWS Result', $fileName));
                    continue;
                }

                $statusCode = $result['@metadata']['statusCode'];
                if ($statusCode !== 200) {
                    $this->logger->error(sprintf('Download file %s failed, status code %d', $fileName, $statusCode));
                    continue;
                }

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
            }
            $this->restClient->updateIntegrationWhenDownloadSuccess(new PartnerParams($config));
            
        } catch (Exception $ex) {
            if ($ex instanceof S3Exception) {
                // throw RuntimeException for retry
                throw new RuntimeException($ex->getMessage());
            }

            // other exceptions will not be retried
            throw $ex;
        }
    }

    /**
     * check if is new file
     *
     * @param DateTime $startDate
     * @param DateTime $endDate
     * @param DateTimeResult $lastModified
     * @return bool
     */
    private function isNewFile(DateTime $startDate, DateTime $endDate, DateTimeResult $lastModified)
    {
        return ($startDate < $lastModified) && ($lastModified < $endDate);
    }
}