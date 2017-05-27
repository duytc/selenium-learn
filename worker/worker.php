<?php
// needed for handling signals
declare(ticks = 1);

$pid = getmypid();
$requestStop = false;

// when TERM signal is sent to this process, we gracefully shutdown after current job is finished processing
// when KILL signal is sent (i.e ctrl-c) we stop immediately
// You can test this by calling "kill -TERM PID" where PID is the PID of this process, the process will end after the current job
pcntl_signal(SIGTERM, function () use (&$requestStop, $pid, &$logger) {
    $logger->notice(sprintf("Worker PID %d has received a request to stop gracefully", $pid));
    $requestStop = true; // set reference value to true to stop worker loop after current job
});
// exit successfully after this time, supervisord will then restart
// this is to prevent any memory leaks from running PHP for a long time
const WORKER_TIME_LIMIT = 10800; // 3 hours
const RESERVE_TIMEOUT = 10; // seconds
// Set the start time
$startTime = time();
$loader = require_once __DIR__ . '/../app/autoload.php';

require_once __DIR__ . '/../app/AppKernel.php';

$env = getenv('SYMFONY_ENV') ?: 'prod';
$debug = false;

if ($env == 'dev') {
    $debug = true;
}

$kernel = new AppKernel($env, $debug);
$kernel->boot();

/** @var \Symfony\Component\DependencyInjection\ContainerInterface $container */
$container = $kernel->getContainer();

$logger = $container->get('logger');
$logHandler = new \Monolog\Handler\StreamHandler("php://stderr", \Monolog\Logger::DEBUG);
$logHandler->setFormatter(new \Monolog\Formatter\LineFormatter(null, null, false, true));
$logger->pushHandler($logHandler);

$tube = $container->getParameter('fetcher_worker_tube');
$queue = $container->get("leezy.pheanstalk");
// only tasks listed here are able to run
$availableWorkers = [
    $container->get('tagcade.worker.workers.execute_integration_job_worker'),
];

$workerPool = new \Tagcade\Worker\Pool($availableWorkers);
$logger->notice(sprintf("Worker PID %d has started", $pid));

while (true) {
    if ($requestStop) {
        // exit worker gracefully, supervisord will restart it
        $logger->notice(sprintf("Worker PID %d is stopping by user request", $pid));
        break;
    }

    if (time() > ($startTime + WORKER_TIME_LIMIT)) {
        // exit worker gracefully, supervisord will restart it
        $logger->notice(sprintf("Worker PID %d is stopping because time limit has been exceeded", $pid));
        break;
    }
    $job = $queue->watch($tube)
        ->ignore('default')
        ->reserve(RESERVE_TIMEOUT);
    if (!$job) {
        continue;
    }
    $worker = null; // important to reset the worker every loop
    $rawPayload = $job->getData();
    $payload = json_decode($rawPayload);
    if (!$payload) {
        $logger->error(sprintf('Received an invalid payload %s', $rawPayload));
        $queue->bury($job);
        continue;
    }
    $task = $payload->task;
    $params = $payload->params;
    $worker = $workerPool->findWorker($task);
    if (!$worker) {
        $logger->error(sprintf('The task "%s" is unknown', $task));
        $queue->bury($job);
        continue;
    }
    if (!$params instanceof Stdclass) {
        $logger->error(sprintf('The task parameters are not valid', $task));
        $queue->bury($job);
        continue;
    }
    $logger->notice(sprintf('Received job %s (ID: %s) with payload %s', $task, $job->getId(), $rawPayload));
    try {
        $worker->$task($params); // dynamic method call
        $logger->notice(sprintf('Job %s (ID: %s) with payload %s has been completed', $task, $job->getId(), $rawPayload));
        $job = $queue->peek($job->getId());
        if ($job) $queue->delete($job);
// task finished successfully
    } catch (Exception $e) {
        $logger->warning(
            sprintf(
                'Job %s (ID: %s) with payload %s failed with an exception: %s',
                $task,
                $job->getId(),
                $rawPayload,
                $e->getMessage()
            )
        );
        $job = $queue->peek($job->getId());
        if ($job) $queue->bury($job);
    }
    gc_collect_cycles();
}
