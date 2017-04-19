<?php

namespace Tagcade\Worker\Workers;


// responsible for doing the background tasks assigned by the manager
// all public methods on the class represent tasks that can be done

use Exception;
use Monolog\Logger;
use stdClass;
use Symfony\Component\Process\Process;

class ExecuteIntegrationJobWorker
{
    const RUN_COMMAND = 'tc:unified-report-fetcher:integration:run';
    const TEM_FILE_NAME_PREFIX = 'integration_config_';

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

    /**
     * GetPartnerReportWorker constructor.
     * @param Logger $logger
     * @param $logDir
     * @param $tempFileDir
     * @param $pathToSymfonyConsole
     * @param $environment
     * @param $debug
     * @param int $processTimeout
     */
    public function __construct(Logger $logger, $logDir, $tempFileDir, $pathToSymfonyConsole, $environment, $debug, $processTimeout)
    {
        $this->logger = $logger;
        $this->logDir = $logDir;
        $this->tempFileDir = $tempFileDir;
        $this->pathToSymfonyConsole = $pathToSymfonyConsole;
        $this->environment = $environment;
        $this->debug = $debug;
        $this->processTimeout = $processTimeout;
    }

    /**
     * get Partner Report
     *
     * @param stdClass $params
     */
    public function executeIntegration(stdClass $params)
    {
        // create integration config file in temp dir
        if (!is_dir($this->tempFileDir)) {
            mkdir($this->tempFileDir, 0777, true);
        }

        $executionRunId = strtotime('now');
        $integrationConfigFileName = sprintf('%s%s.json', self::TEM_FILE_NAME_PREFIX, $executionRunId);
        $integrationConfigFilePath = $this->createTempIntegrationConfigFile($integrationConfigFileName, $executionRunId);

        //// write config to file
        $fpIntegrationConfigFile = fopen($integrationConfigFilePath, 'w+');
        fwrite($fpIntegrationConfigFile, json_encode($params));

        // open log file file in log dir
        if (!is_dir($this->logDir)) {
            mkdir($this->logDir, 0777, true);
        }

        $logFile = sprintf('%s/run_log_%d.log', $this->logDir, $executionRunId);
        $fpLogger = fopen($logFile, 'a');

        // create process to wrap command
        $process = new Process(sprintf('%s %s %s', $this->getAppConsoleCommand(), self::RUN_COMMAND, $integrationConfigFilePath));
        $process->setTimeout($this->processTimeout);

        try {
            $process->mustRun(
                function ($type, $buffer) use (&$fpLogger) {
                    fwrite($fpLogger, $buffer);
                }
            );
        } catch (Exception $e) {
            $this->logger->warning(sprintf('Execution run failed (exit code %d), please see %s for more details', $process->getExitCode(), $logFile));
        } finally {
            // close file
            fclose($fpLogger);
            fclose($fpIntegrationConfigFile);

            // remove temp file
            unlink($integrationConfigFilePath);
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
     * @param $suffix
     * @return string
     */
    private function createTempIntegrationConfigFile($fileName, $suffix)
    {
        if (!is_dir($this->tempFileDir)) {
            mkdir($this->tempFileDir, 0777, true);
        }

        $tempFileName = sprintf('%s_%s.json', $fileName, $suffix);
        $integrationConfigFile = sprintf('%s/%s', $this->tempFileDir, $tempFileName);
        return $integrationConfigFile;
    }
}