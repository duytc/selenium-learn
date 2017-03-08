<?php


namespace Tagcade\Service\Integration\Integrations\AWS;


use Aws\Api\DateTimeResult;
use Aws\Result;
use Aws\S3\S3Client;
use DateInterval;
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
        $awsKey = $allParams[self::PARAM_AWS_KEY];
        $awsSecret = $allParams[self::PARAM_AWS_SECRET];
        $awsRegion = $allParams[self::PARAM_AWS_REGION];

        if (!array_key_exists(self::PARAM_START_DATE, $allParams)) {
            $startDate = new DateTime('yesterday');
        } else {
            $startDate = new DateTime($allParams[self::PARAM_START_DATE]);
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
            if (!$this->isNewFile($fileName, $startDate, $lastModified)) {
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
            if ($result['Body']) {
                //
            }
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

        if (array_key_exists(self::PARAM_START_DATE, $allParameters)) {
            $startDateStr = $allParameters[self::PARAM_START_DATE];

            try {
                $startDate = date_create_from_format('Y-m-d', $startDateStr);

                if (false === $startDate) {
                    $this->logger->error(sprintf('Invalid date %s in parameters', $startDateStr));
                    throw new Exception(sprintf('Invalid date %s in parameters', $startDateStr));
                }
            } catch (Exception $e) {
                $this->logger->error(sprintf('Try parse date %s from parameters got error %s', $startDateStr, $e->getMessage()));
                throw new Exception(sprintf('Try parse date %s from parameters got error %s', $startDateStr, $e->getMessage()));
            }
        }
    }

    /**
     * check if is new file
     *
     * @param string $fileName
     * @param DateTime $startDate
     * @param DateTimeResult $lastModified
     * @return bool
     */
    private function isNewFile($fileName, DateTime $startDate, DateTimeResult $lastModified)
    {
        /** @var DateInterval $interval */
        $interval = $lastModified->diff($startDate);
        if (false == $interval) {
            $this->logger->error(sprintf('Can not diff startDate %s with lastModified %s for file %s', $startDate->format('Y-m-d'), $lastModified->format('Y-m-d'), $fileName));
            return false;
        }

        if ($interval->days >= 1) {
            return false;
        }

        return true;
    }
}