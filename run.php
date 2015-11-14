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

$log = new Logger('main');
$log->pushHandler(new StreamHandler('php://stdout', Logger::INFO));

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
    $chromeOptions->addArguments([sprintf("user-data-dir=%s/chrome/profile", DATA_PATH)]);
    $chromeOptions->setExperimentalOption('prefs', [
       "download.default_directory" => "/home/greg/Tagcade/report-automation/data/downloads",
    ]);

    $capabilities = DesiredCapabilities::chrome();
    $capabilities->setCapability(ChromeOptions::CAPABILITY, $chromeOptions);

    $driver = RemoteWebDriver::create('http://localhost:4444/wd/hub', $capabilities);

    $sessionId = $driver->getSessionID();

    echo sprintf("Session created: %s\n", $sessionId);
    
    file_put_contents(SESSION_FILE, $sessionId);
}

$webDriverTimeouts = $driver->manage()->timeouts();

$webDriverTimeouts->implicitlyWait(10);
$webDriverTimeouts->pageLoadTimeout(10);

$reportSelectorWidget = new PulsePoint\Widget\ReportSelectorWidget(
    $driver,
    new PulsePoint\Widget\ReportTypeWidget($driver),
    new PulsePoint\Widget\DateRangeWidget($driver),
    new PulsePoint\Widget\RunButtonWidget($driver)
);

$exportButtonWidget = new PulsePoint\Widget\ExportButtonWidget($driver);

$managerPage = new PulsePoint\Page\ManagerPage($driver, $reportSelectorWidget, $exportButtonWidget);
$managerPage->setLogger($log);
$loginPage = new PulsePoint\Page\LoginPage($driver);
$loginPage->setLogger($log);

if (!$managerPage->isCurrentUrl()) {
    $managerPage->navigate();

    if ($loginPage->isCurrentUrl()) {
        $loginPage->login(PULSEPOINT_USERNAME, PULSEPOINT_PASSWORD);
    }
}

$reportDate = new DateTime('yesterday');

// temporary for developing
$managerPage->enableReceiveReportsByEmail(false);

$managerPage
    ->setEmailAddress(REPORT_EMAIL)
    ->getAccountManagementReport($reportDate)
    ->getDailyStatsReport($reportDate)
    ->getImpressionDomainsReports($reportDate)
;
