<?php

namespace Tagcade\Worker\Workers;


// responsible for doing the background tasks assigned by the manager
// all public methods on the class represent tasks that can be done

use Monolog\Logger;
use stdClass;
use Symfony\Component\Process\Process;

class ExecuteIntegrationJobWorker
{
    const PHP_BIN = 'php ../app/console';
    const RUN_COMMAND = 'tc:unified-report-fetcher:execute:integration:job';
    const TEM_FILE_NAME_PREFIX = 'integration_config_';

    /**
     * @var Logger $logger
     */
    private $logger;

    private $logDir;

    private $tempFileDir;

    /**
     * GetPartnerReportWorker constructor.
     * @param Logger $logger
     * @param $logDir
     * @param $tempFileDir
     */
    public function __construct(Logger $logger, $logDir, $tempFileDir)
    {
        $this->logger = $logger;
        $this->logDir = $logDir;
        $this->tempFileDir = $tempFileDir;
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
            mkdir($this->logDir);
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

        $process = new Process(sprintf('%s %s %s', self::PHP_BIN, self::RUN_COMMAND, $tempFileName));

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
}