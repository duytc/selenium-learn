<?php

require __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\Process\Process;

// By default supervisor sends all stdout and stderr into a single file,
// You can emulate this with php7.0 test_integration.php >> logs/log 2>> logs/error
// This makes debugging hard, since you don't know where the logs came from

// here is a simple example of wrapping a script and capturing the stdout and stderr and putting into a named file containing the import id

$importId = rand(1, 10000);

$process = new Process('php7.0 test_integration.php 2>&1');
$process->run();

// can also use $process->getIncrementalOutput()

file_put_contents(__DIR__ . sprintf('/logs/import_log_%d', $importId), $process->getOutput());

echo $process->getOutput();