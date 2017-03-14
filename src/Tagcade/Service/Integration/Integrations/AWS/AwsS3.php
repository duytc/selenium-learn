<?php


namespace Tagcade\Service\Integration\Integrations\AWS;


use Aws\Api\DateTimeResult;
use Aws\Result;
use Aws\S3\S3Client;
use DateTime;
use Exception;
use Psr\Log\LoggerInterface;
use Tagcade\Service\FileStorageService;
use Tagcade\Service\Integration\ConfigInterface;
use Tagcade\Service\Integration\Integrations\IntegrationAbstract;
use Tagcade\Service\Integration\Integrations\IntegrationInterface;

class AwsS3 extends IntegrationAbstract implements IntegrationInterface
{
    const INTEGRATION_C_NAME = 'aws-s3';

    const PARAM_BUCKET = 'bucket';
    const PARAM_PATTERN = 'pattern';
    const PARAM_AWS_KEY = 'aws_key';
    const PARAM_AWS_SECRET = 'aws_secret';
    const PARAM_AWS_REGION = 'aws_region';
    const PARAM_VERSION = 'version';
    const PARAM_START_DATE = 'startDate';
    const PARAM_END_DATE = 'endDate';

    const VALUE_VERSION_DEFAULT = 'latest';

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var FileStorageService
     */
    private $fileStorage;

    /**
     * AwsS3 constructor.
     * @param LoggerInterface $logger
     * @param FileStorageService $fileStorage
     */
    public function __construct(LoggerInterface $logger, FileStorageService $fileStorage)
    {
        $this->logger = $logger;
        $this->fileStorage = $fileStorage;
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
        $startDate = new DateTime($config->getParamValue(self::PARAM_START_DATE, 'yesterday'));
        $endDate = new DateTime($config->getParamValue(self::PARAM_END_DATE, 'yesterday'));

        // validate required params
        // TODO: validate start/end date too
        if (empty($bucket) || empty($filePattern) || empty($awsKey) || empty($awsSecret) || empty($awsRegion)) {
            $this->logger->error('missing parameter values for either bucket or filePattern or awsKey or awsSecret or awsRegion');
            throw new Exception('missing parameter values for either bucket or filePattern or awsKey or awsSecret or awsRegion');
        }

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

            $path = $this->fileStorage->getDownloadPath($config, $fileName);

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
            }

            // create metadata file.
            // metadata file contains file pattern, so it lets directory monitory has information to get exact data source relates to file pattern
            $metadata = [
                'module' => 'integration',
                'integrationCName' => self::INTEGRATION_C_NAME,
                'pattern' => $filePattern
            ];
            $metadataFilePath = $path . '.meta';
            file_put_contents($metadataFilePath, json_encode($metadata));
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