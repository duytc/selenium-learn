<?php

namespace Tagcade\Worker\Workers;


// responsible for doing the background tasks assigned by the manager
// all public methods on the class represent tasks that can be done

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

    /**
     * GetPartnerReportWorker constructor.
     * @param Logger $logger
     * @param $logDir
     * @param $tempFileDir
     * @param $pathToSymfonyConsole
     * @param $environment
     * @param $debug
     */
    public function __construct(Logger $logger, $logDir, $tempFileDir, $pathToSymfonyConsole, $environment, $debug)
    {
        $this->logger = $logger;
        $this->logDir = $logDir;
        $this->tempFileDir = $tempFileDir;
        $this->pathToSymfonyConsole = $pathToSymfonyConsole;
        $this->environment = $environment;
        $this->debug = $debug;
    }

    /**
     * get Partner Report
     *
     * @param stdClass $params
     */
    public function executeIntegration(stdClass $params)
    {
        $executionRunId = strtotime("now");

        if (!is_dir($this->logDir)) {
            mkdir($this->logDir, 0777, true);
        }

        if (!is_dir($this->tempFileDir)) {
            mkdir($this->tempFileDir, 0777, true);
        }

        $logFile = sprintf('%s/run_log_%d.log', $this->logDir, $executionRunId);
        $tempFileName = sprintf('%s%s.json', self::TEM_FILE_NAME_PREFIX, $executionRunId);
        $integrationConfigFile = sprintf('%s/%s', $this->tempFileDir, $tempFileName);

        $fp = fopen($logFile, 'a');
        $fp1 = fopen($integrationConfigFile, 'a');
        fwrite($fp1, json_encode($params));

        $process = new Process(sprintf('%s %s %s', $this->getAppConsoleCommand(), self::RUN_COMMAND, $tempFileName));

        try {
            $process->mustRun(
                function ($type, $buffer) use (&$fp) {
                    fwrite($fp, $buffer);
                }
            );
        } catch (\Exception $e) {
            $this->logger->warning(sprintf('Execution run failed (exit code %d), please see %s for more details', $process->getExitCode(), $logFile));
        } finally {
            fclose($fp);
            fclose($fp1);
            unlink($integrationConfigFile);
        }
    }

    protected function getAppConsoleCommand()
    {
        $phpBin = PHP_BINARY;
        $command = sprintf('%s %s/console --env=%s', $phpBin, $this->pathToSymfonyConsole, $this->environment);

        if (!$this->debug) {
            $command .= ' --no-debug';
        }

        return $command;
    }
}