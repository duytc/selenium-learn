<?php


namespace Tagcade\Service\Integration\Integrations\AWS;


use Aws\S3\S3Client;
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
        // validate params
        $allParams = $config->getParams();
        if (!is_array($allParams)) {
            $this->logger->error('expect config parameters is array');
            throw new Exception('expect config parameters is array');
        }

        $this->validateParameters($allParams);

        // get all params
        $bucket = $allParams[self::PARAM_BUCKET];
        $filePattern = $allParams[self::PARAM_PATTERN];

        // TODO: remove unused startDate
        if (!array_key_exists('startDate', $allParams)) {
            $startDate = new \DateTime('yesterday');
        } else {
            $startDate = new \DateTime($allParams['startDate']);
        }

        $awsKey = $allParams[self::PARAM_AWS_KEY];
        $awsSecret = $allParams[self::PARAM_AWS_SECRET];
        $awsRegion = $allParams[self::PARAM_AWS_SECRET];

        $s3 = new S3Client([
            'credentials' => [
                'key' => $awsKey,
                'secret' => $awsSecret,
            ],
            'region' => $awsRegion,
            'version' => self::VALUE_VERSION_DEFAULT,
        ]);

        $iterator = $s3->getIterator('ListObjects', array('Bucket' => $bucket));

        foreach ($iterator as $object) {
            $key = $object['Key'];
            if (!preg_match($filePattern, $key)) {
                continue;
            }

            /**
             * @var \Aws\Api\DateTimeResult $lastModified
             */
            $lastModified = $object['LastModified'];
            $interval = $lastModified->diff($startDate);
            if ($interval->invert == 1) {
                continue;
            }

            $fileName = bin2hex(random_bytes(10));
            $path = $this->fileStorage->getDownloadPath($config, $fileName);

            $s3->getObject(array(
                'Bucket' => $bucket,
                'Key' => $key,
                'SaveAs' => $path
            ));
        }
    }

    /**
     * @param array $allParameters
     * @throws Exception
     */
    private function validateParameters(array $allParameters)
    {
        if (!array_key_exists(self::PARAM_BUCKET, $allParameters)) {
            $this->logger->error(sprintf('Missing %s in parameters', self::PARAM_BUCKET));
            throw new Exception(sprintf('Missing %s in parameters', self::PARAM_BUCKET));
        }

        if (!array_key_exists(self::PARAM_PATTERN, $allParameters)) {
            $this->logger->error(sprintf('Missing %s in parameters', self::PARAM_PATTERN));
            throw new Exception(sprintf('Missing %s in parameters', self::PARAM_PATTERN));
        }

        if (!array_key_exists(self::PARAM_AWS_KEY, $allParameters)) {
            $this->logger->error(sprintf('Missing %s in parameters', self::PARAM_AWS_KEY));
            throw new Exception(sprintf('Missing %s in parameters', self::PARAM_AWS_KEY));
        }

        if (!array_key_exists(self::PARAM_AWS_SECRET, $allParameters)) {
            $this->logger->error(sprintf('Missing %s in parameters', self::PARAM_AWS_SECRET));
            throw new Exception(sprintf('Missing %s in parameters', self::PARAM_AWS_SECRET));
        }

        if (!array_key_exists(self::PARAM_AWS_REGION, $allParameters)) {
            $this->logger->error(sprintf('Missing %s in parameters', self::PARAM_AWS_REGION));
            throw new Exception(sprintf('Missing %s in parameters', self::PARAM_AWS_REGION));
        }
    }
}