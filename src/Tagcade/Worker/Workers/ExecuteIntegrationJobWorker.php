<?php

namespace Tagcade\Worker\Workers;


// responsible for doing the background tasks assigned by the manager
// all public methods on the class represent tasks that can be done

use Exception;
use Monolog\Logger;
use Tagcade\Service\Core\TagcadeRestClient;
use Tagcade\Service\Fetcher\Params\PartnerParams;
use Tagcade\Service\Lock\LockServiceInterface;
use RuntimeException;
use stdClass;
use Tagcade\Exception\LoginFailException;
use Tagcade\Service\Integration\Config;
use Tagcade\Service\Integration\ConfigInterface;
use Tagcade\Service\Integration\IntegrationManagerInterface;
use Tagcade\Service\Integration\Integrations\IntegrationInterface;

class ExecuteIntegrationJobWorker
{
    const JOB_FAILED_CODE = 1;
    const JOB_DONE_CODE = 0;
    const JOB_LOCKED_CODE = 455;

    /**
     * @var Logger $logger
     */
    private $logger;

    private $lockService;

    private $fetcherManager;

    private $maxRetriesNumber;

    private $delayBeforeRetry;

    /** @var TagcadeRestClient  */
    private $restClient;

    /**
     * GetPartnerReportWorker constructor.
     * @param Logger $logger
     * @param LockServiceInterface $lockService
     * @param IntegrationManagerInterface $fetcherManager
     * @param $maxRetriesNumber
     * @param $delayBeforeRetry
     * @param TagcadeRestClient $restClient
     */
    public function __construct(Logger $logger, LockServiceInterface $lockService, IntegrationManagerInterface $fetcherManager, $maxRetriesNumber, $delayBeforeRetry, TagcadeRestClient $restClient)
    {
        $this->logger = $logger;
        $this->lockService = $lockService;
        $this->fetcherManager = $fetcherManager;
        $this->maxRetriesNumber = $maxRetriesNumber;
        $this->delayBeforeRetry = $delayBeforeRetry;
        $this->restClient = $restClient;
    }

    /**
     * get Partner Report
     *
     * @param stdClass $params
     * @return int
     */
    public function executeIntegration(stdClass $params)
    {
        if (!isset($params->integrationCName)) {
            $this->logger->error(sprintf('missing integration CName in params %s', serialize($params)));
            return self::JOB_FAILED_CODE;
        }

        $cname = $params->integrationCName;

        $lock = $this->lockService->lock(sprintf('integration-%s-lock', $cname));

        if ($lock === false) {
            $this->logger->notice(sprintf('integration cname %s is currently locked', $cname));
            return self::JOB_LOCKED_CODE;
        }

        $dataSourceId = $params->dataSourceId ? $params->dataSourceId : 0; // 0 is unknown...
        /** @var ConfigInterface $config */
        $config = new Config($params->publisherId, $cname, $dataSourceId, json_decode($params->params, true), json_decode($params->backFill, true));

        /** @var IntegrationInterface $integration */
        $integration = $this->fetcherManager->getIntegration($config);

        /* run integration, supported retry mechanism */
        // get params
        if (!is_integer($this->maxRetriesNumber) || $this->maxRetriesNumber < 0) {
            $this->logger->error(sprintf('missing or invalid parameter retry_when_fail_max_retries_number. Expected a positive integer value.'));
            return self::JOB_LOCKED_CODE;
        }

        if (!is_integer($this->delayBeforeRetry) || $this->delayBeforeRetry < 0) {
            $this->logger->error(sprintf('missing or invalid parameter retry_when_fail_delay_before_retry. Expected a positive integer value (in seconds).'));
            return self::JOB_LOCKED_CODE;
        }

        // run
        $retriedNumber = 0;
        do {
            try {
                if ($retriedNumber > 0) {
                    // delay retry
                    sleep($this->delayBeforeRetry);

                    $this->logger->info(sprintf('Integration run retry [%d].', $retriedNumber));
                }

                $integration->run($config);

                break; // break while loop if success (no exception is threw)
            } catch (LoginFailException $loginFailException) {
                $retriedNumber++;

                $this->logger->error(sprintf('Integration run got LoginFailException: %s.', $loginFailException->getMessage()));
            } catch (RuntimeException $runtimeException) {
                $retriedNumber++;

                $this->logger->error(sprintf('Integration run got RuntimeException: %s.', $runtimeException->getMessage()));
            } catch (\Exception $ex) {
                // do not retry for other exceptions because the exception may come from wrong config, data, filePath, ...
                // so the retry is invalid
                $this->logger->error(sprintf('Integration run got Exception: %s. Skip to next integration job.', $ex->getMessage()));
                $this->restClient->updateIntegrationWhenRunFail(new PartnerParams($config));
                break; // break while loop if other errors
            }
        } while ($retriedNumber <= $this->maxRetriesNumber);

        if ($retriedNumber > 0 && $retriedNumber > $this->maxRetriesNumber) {
            $this->logger->info(sprintf('Integration run got max retries number: %d. Skip to next integration job.', $this->maxRetriesNumber));
            $this->restClient->updateIntegrationWhenRunFail(new PartnerParams($config));
        }

        $this->logger->info('Release lock');
        $this->lockService->unlock($lock);
    }
}