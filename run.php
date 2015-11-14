<?php

require 'config.php';
require 'config.credentials.php';
require 'vendor/autoload.php';

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Tagcade\DataSource\PulsePoint as PulsePoint;

$logger = new Logger('main');
$logger->pushHandler(new StreamHandler('php://stdout', Logger::DEBUG));

$options = getopt('', ['env:', 'data-path:', 'session-id:']);

$options = array_merge([
    'env'       => 'dev',
    'data-path' => DATA_PATH,
    'session-file' => rtrim(DATA_PATH, '/') . '/.session'
], $options);

if (!is_writable($options['data-path'])) {
    $logger->critical('Cannot write to data path');
    exit(1);
}

$driver = \Tagcade\WebDriverFactory::getWebDriver($options, $logger);

$driver->manage()
    ->timeouts()
    ->implicitlyWait(3)
    ->pageLoadTimeout(10)
;

$params = (new PulsePoint\TaskParams())
    ->setUsername(PULSEPOINT_USERNAME)
    ->setPassword(PULSEPOINT_PASSWORD)
    ->setEmailAddress(REPORT_EMAIL)
    ->setReportDate(new DateTime('yesterday'))
;

$params->setReceiveReportsByEmail(false); // for development purposes

PulsePoint\TaskFactory::getAllData($driver, $params, $logger);

$logger->info('Application finished');

if ('prod' == $options['env']) {
    $driver->quit();
}