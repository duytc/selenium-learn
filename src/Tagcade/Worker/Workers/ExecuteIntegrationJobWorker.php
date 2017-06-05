<?php

namespace Tagcade\Worker\Workers;


// responsible for doing the background tasks assigned by the manager
// all public methods on the class represent tasks that can be done

use Exception;
use Leezy\PheanstalkBundle\Proxy\PheanstalkProxyInterface;
use Monolog\Logger;
use stdClass;
use Symfony\Component\Filesystem\LockHandler;
use Symfony\Component\Process\Process;
use Tagcade\Service\DeleteFileService;

class ExecuteIntegrationJobWorker
{
    const JOB_FAILED_CODE = 1;
    const JOB_DONE_CODE = 0;
    const JOB_LOCKED_CODE = 455;

    const RUN_COMMAND = 'tc:unified-report-fetcher:integration:run';

    /**
     * %s %s is {data source id} {timestamps}
     *
     * e.g integration_config_datasource_123_4283432948923.json
     */
    const TEM_FILE_NAME_TEMPLATE = 'integration_config_datasource_%d_%s.json';

    /**
     * %s is {data source id}. Write all log to only one file for each data source
     *
     * e.g run_log_datasource_123.log
     */
    const LOG_FILE_NAME_TEMPLATE = 'run_log_datasource_%d.log';

    /**
     * @var Logger $logger
     */
    private $logger;

    private $logDir;

    private $tempFileDir;

    private $pathToSymfonyConsole;

    private $environment;

    private $debug;

    private $processTimeout;

    private $queue;

    private $tube;

    private $jobDelay;

    private $ttr;

    /* @var DeleteFileService  */
    private $deleteFileService;

    /**
     * GetPartnerReportWorker constructor.
     * @param PheanstalkProxyInterface $queue
     * @param Logger $logger
     * @param $logDir
     * @param $tempFileDir
     * @param $pathToSymfonyConsole
     * @param $environment
     * @param $debug
     * @param int $processTimeout
     * @param $tube
     */
    public function __construct(PheanstalkProxyInterface $queue, Logger $logger, $logDir, $tempFileDir, $pathToSymfonyConsole, $environment, $debug, $processTimeout, $tube, $jobDelay, $ttr, DeleteFileService $deleteFileService)
    {
        $this->logger = $logger;
        $this->logDir = $logDir;
        $this->tempFileDir = $tempFileDir;
        $this->pathToSymfonyConsole = $pathToSymfonyConsole;
        $this->environment = $environment;
        $this->debug = $debug;
        $this->processTimeout = $processTimeout;
        $this->queue = $queue;
        $this->tube = $tube;
        $this->jobDelay = $jobDelay;
        $this->ttr = $ttr;
        $this->deleteFileService = $deleteFileService;
    }

    /**
     * get Partner Report
     *
     * @param stdClass $params
     * @return int
     */
    public function executeIntegration(stdClass $params)
    {
        // create integration config file in temp dir
        if (!is_dir($this->tempFileDir)) {
            mkdir($this->tempFileDir, 0777, true);
        }

        if (!isset($params->integrationCName)) {
            $this->logger->error(sprintf('missing integration CName in params %s', serialize($params)));
            return self::JOB_FAILED_CODE;
        }

        $cname = $params->integrationCName;
        $lock = new LockHandler(sprintf('integration-%s-lock', $cname));

        if (!$lock->lock()) {
            $this->logger->notice(sprintf('integration cname %s is currently locked', $cname));
            return self::JOB_LOCKED_CODE;
        }

        $dataSourceId = $params->dataSourceId ? $params->dataSourceId : 0; // 0 is unknown...
        $executionRunId = strtotime('now');
        $integrationConfigFileName = sprintf(self::TEM_FILE_NAME_TEMPLATE, $dataSourceId, $executionRunId);
        $integrationConfigFilePath = $this->createTempIntegrationConfigFile($integrationConfigFileName);

        //// write config to file
        $fpIntegrationConfigFile = fopen($integrationConfigFilePath, 'w+');
        fwrite($fpIntegrationConfigFile, json_encode($params));

        // open log file file in log dir
        if (!is_dir($this->logDir)) {
            mkdir($this->logDir, 0777, true);
        }

        $logFile = sprintf('%s/%s', $this->logDir, sprintf(self::LOG_FILE_NAME_TEMPLATE, $dataSourceId));
        $fpLogger = fopen($logFile, 'a');

        // create process to wrap command
        $process = new Process(sprintf('%s %s %s', $this->getAppConsoleCommand(), self::RUN_COMMAND, $integrationConfigFilePath), $this->ttr);
        $process->setTimeout($this->processTimeout);

        try {
            $process->mustRun(
                function ($type, $buffer) use (&$fpLogger) {
                    fwrite($fpLogger, $buffer);
                }
            );
            return self::JOB_DONE_CODE;
        } catch (Exception $e) {
            $this->logger->warning(sprintf('Execution run failed (exit code %d), please see %s for more details', $process->getExitCode(), $logFile));
            return self::JOB_FAILED_CODE;
        } finally {
            $this->logger->info('Release lock');
            $lock->release();
            // close file
            fclose($fpLogger);
            fclose($fpIntegrationConfigFile);

            // remove temp file
            if (is_file($integrationConfigFilePath)) {
                $this->deleteFileService->removeFileOrFolder($integrationConfigFilePath);
            }
        }
    }

    /**
     * get App Console Command
     *
     * @return string
     */
    protected function getAppConsoleCommand()
    {
        $phpBin = PHP_BINARY;
        $command = sprintf('%s %s/console --env=%s', $phpBin, $this->pathToSymfonyConsole, $this->environment);

        if (!$this->debug) {
            $command .= ' --no-debug';
        }

        return $command;
    }

    /**
     * create Temp Integration Config File
     *
     * @param $fileName
     * @return string
     */
    private function createTempIntegrationConfigFile($fileName)
    {
        if (!is_dir($this->tempFileDir)) {
            mkdir($this->tempFileDir, 0777, true);
        }

        $integrationConfigFile = sprintf('%s/%s', $this->tempFileDir, $fileName);
        return $integrationConfigFile;
    }
}