<?php

use Monolog\Handler\StreamHandler;
use Symfony\Component\Process\Process;

$loader = require_once __DIR__ . '/../app/autoload.php';
require_once __DIR__ . '/../app/AppKernel.php';

$kernel = new AppKernel('dev', true);
$kernel->boot();

$container = $kernel->getContainer();

$logger = $container->get('logger');

// only show log messages above warning in the top level process, this keeps supervisor log clean
$logger->pushHandler(new StreamHandler("php://stderr", \Monolog\Logger::WARNING));

const PHP_BIN = '/usr/bin/php7.0';
// in prod would probably be a symfony console command with args
const RUN_COMMAND = 'test_integration.php';

// for fetcher logs, we need to come up with a run id or execution run id. In UR API, this would be the import id
$executionRunId = rand(1, 50000);

$logFile = __DIR__ . sprintf('/logs/run_log_%d.log', $executionRunId);

$fp = fopen($logFile, 'a');

$process = new Process(sprintf('%s %s', PHP_BIN, RUN_COMMAND));

try {
    $process->mustRun(
//    $process->run(
        function ($type, $buffer) use (&$fp) {
            fwrite($fp, $buffer);
            // We can also display logs in this shell as well
            //    echo $buffer;
        }
    );
} catch (\Exception $e) {
    // top level log is very clean. This is the supervisor log but it provides the name of the specific file for more debugging
    // if the admin wants to know more about the failure, they have the exact log file
    $logger->warning(sprintf('Execution run failed, please see %s for more details', $logFile));
} finally {
    fclose($fp);
}