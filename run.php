<?php

require 'config.php';
require 'config.credentials.php';
require 'vendor/autoload.php';

use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Exception\UnknownServerException;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Tagcade\DataSource\PulsePoint as PulsePoint;

$logger = new Logger('main');
$logger->pushHandler(new StreamHandler('php://stdout', Logger::DEBUG));

$options = getopt('', ['session-id:']);

$sessionId = null;

if (isset($options['session-id'])) {
    $sessionId = $options['session-id'];
    echo sprintf("Using existing session: %s\n", $sessionId);
} else if (file_exists(SESSION_FILE)) {
    $sessionId = file_get_contents(SESSION_FILE);
    echo sprintf("Using existing session from saved file: %s\n", $sessionId);
}

if ($sessionId) {
    $availableSessions = array_map(function(array $session) {
        return $session['id'];
    }, RemoteWebDriver::getAllSessions());

    if (!in_array($sessionId, $availableSessions)) {
        error_log("The supplied session id does not exist");
        exit(1);
    }

    $driver = RemoteWebDriver::createBySessionID($sessionId);

    try {
        // do a check to see if the existing session has window handles
        $driver->getWindowHandles();
    } catch (UnknownServerException $e) {
        error_log("Could not connect to browser window, did you close it?");
        exit(1);
    }
} else {
    $chromeOptions = new ChromeOptions();
    $chromeOptions->addArguments([sprintf('user-data-dir=%s/chrome/profile', DATA_PATH)]);
    $chromeOptions->setExperimentalOption('prefs', [
       'download.default_directory' => DATA_PATH . '/downloads',
    ]);

    $capabilities = DesiredCapabilities::chrome();
    $capabilities->setCapability(ChromeOptions::CAPABILITY, $chromeOptions);

    $driver = RemoteWebDriver::create('http://localhost:4444/wd/hub', $capabilities);

    $sessionId = $driver->getSessionID();

    echo sprintf("Session created: %s\n", $sessionId);
    
    file_put_contents(SESSION_FILE, $sessionId);
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

$params->setReceiveReportsByEmail(false); // for development purposes

PulsePoint\TaskFactory::getAllData($driver, $params, $logger);

$logger->info('Application finished');