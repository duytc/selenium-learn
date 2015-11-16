<?php

require 'config.php';
require 'config.credentials.php';
require 'vendor/autoload.php';

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Tagcade\DataSource\PulsePoint as PulsePoint;

use Ulrichsg\Getopt\Getopt;
use Ulrichsg\Getopt\Option;

$getopt = new Getopt([
    new Option(null, 'config-path', Getopt::REQUIRED_ARGUMENT),
    (new Option(null, 'data-path', Getopt::REQUIRED_ARGUMENT))->setDefaultValue(DATA_PATH),
    new Option(null, 'disable-email', Getopt::NO_ARGUMENT),
    new Option(null, 'session-id', Getopt::REQUIRED_ARGUMENT),
    new Option(null, 'quit-web-driver-after-run', Getopt::NO_ARGUMENT),
    new Option(null, 'help', Getopt::NO_ARGUMENT),
]);

$getopt->parse();

if ($getopt['help']) {
    echo $getopt->getHelpText();
    exit(0);
}

$logger = new Logger('main');
$logger->pushHandler(new StreamHandler('php://stdout', Logger::DEBUG));

$options = getopt('', ['env:', 'data-path:', 'session-id:', 'disable-email']);

if (!is_writable($getopt['data-path'])) {
    $logger->critical('Cannot write to data path');
    exit(1);
}

if ($getopt['session-id']) {
    $driver = \Tagcade\WebDriverFactory::getExistingSession($getopt['session-id'], $logger);
} else {
    $driver = \Tagcade\WebDriverFactory::getWebDriver($getopt['data-path'], $logger);
}

if (!$driver) {
    $logger->critical('Cannot proceed without web driver');
    exit(1);
}

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

if ($getopt['disable-email'] === false) {
    $logger->info('Disabling email');
    $params->setReceiveReportsByEmail(false);
};

PulsePoint\TaskFactory::getAllData($driver, $params, $logger);

$logger->info('Application finished');

if ($getopt['quit-web-driver-after-run']) {
    $driver->quit();
}