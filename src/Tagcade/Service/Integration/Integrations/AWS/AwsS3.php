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
        $allParams = $config->getParams();
        if (!array_key_exists('bucket', $allParams)) {
            $this->logger->error('Missing bucket in parameters');
            throw new Exception('Missing bucket in parameters');
        }
        $bucket = $allParams['bucket'];

        if (!array_key_exists('pattern', $allParams)) {
            $this->logger->error('Missing pattern in parameters');
            throw new Exception('Missing pattern in parameters');
        }
        $filePattern = $allParams['pattern'];

        if (!array_key_exists('startDate', $allParams)) {
            $startDate = new \DateTime('yesterday');
        } else {
            $startDate = new \DateTime($allParams['startDate']);
        }

        if (!array_key_exists('aws_key', $allParams)) {
            $this->logger->error('Missing aws_key in parameters');
            throw new Exception('Missing aws_key in parameters');
        }
        $awsKey = $allParams['aws_key'];

        if (!array_key_exists('aws_secret', $allParams)) {
            $this->logger->error('Missing aws_secret in parameters');
            throw new Exception('Missing aws_secret in parameters');
        }
        $awsSecret = $allParams['aws_secret'];

        if (!array_key_exists('aws_region', $allParams)) {
            $this->logger->error('Missing aws_region in parameters');
            throw new Exception('Missing aws_region in parameters');
        }
        $awsRegion = $allParams['aws_region'];

        $s3 = new S3Client([
            'credentials' => [
                'key' => $awsKey,
                'secret' => $awsSecret,
            ],
            'region' => $awsRegion,
            'version' => 'latest',
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
}