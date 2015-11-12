<?php

require 'config.php';
require 'config.credentials.php';
require 'vendor/autoload.php';

use Facebook\WebDriver\Exception\NoSuchElementException;
use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Exception\UnknownServerException;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Facebook\WebDriver\WebDriverSelect;

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

const EXPORT_BUTTON_SEL = 'a.exportButton.button';
const REPORT_TYPE_ACCOUNT_MANAGEMENT = '0';
const REPORT_TYPE_IMPRESSION_DOMAINS = '7';
const REPORT_TYPE_DAILY_STATS = '18';

if ($driver->getCurrentURL() != 'http://exchange.pulsepoint.com/Publisher/PMRMainJT.aspx') {
    $driver->navigate()->to('http://exchange.pulsepoint.com/Publisher/PMRMainJT.aspx');

    if (strpos($driver->getCurrentURL(), 'https://exchange.pulsepoint.com/AccountMgmt/Login.aspx') === 0) {
        $driver
            ->findElement(WebDriverBy::id('UserName'))
            ->clear()
            ->sendKeys(PULSEPOINT_USERNAME)
        ;

        $driver
            ->findElement(WebDriverBy::id('Password'))
            ->clear()
            ->sendKeys(PULSEPOINT_PASSWORD)
        ;

        $driver->findElement(WebDriverBy::id('LoginButton'))->click();
        $driver->findElement(WebDriverBy::cssSelector('.tab.manager'))->click();
    }
}

sleep(2);

$reportTypeSelect = new WebDriverSelect($driver->findElement(WebDriverBy::id('ddlReportTypes')));
$runReportButton = $driver->findElement(WebDriverBy::cssSelector('.runReportButton a'));

//////////////

$reportTypeSelect->selectByValue(REPORT_TYPE_ACCOUNT_MANAGEMENT);
$runReportButton->click();
sleep(2);
$driver->wait()->until(WebDriverExpectedCondition::elementToBeClickable(WebDriverBy::cssSelector(EXPORT_BUTTON_SEL)));
echo "Downloading account management report\n";
$driver->findElement(WebDriverBy::cssSelector(EXPORT_BUTTON_SEL))->click();
sleep(1);

////////////////

$reportTypeSelect->selectByValue(REPORT_TYPE_DAILY_STATS);
$runReportButton->click();
sleep(2);
$driver->wait()->until(WebDriverExpectedCondition::elementToBeClickable(WebDriverBy::cssSelector(EXPORT_BUTTON_SEL)));
echo "Downloading daily stats report\n";
$driver->findElement(WebDriverBy::cssSelector(EXPORT_BUTTON_SEL))->click();
sleep(1);

//////////////

$reportTypeSelect->selectByValue(REPORT_TYPE_IMPRESSION_DOMAINS);

$adTagFilter = new WebDriverSelect($driver->findElement(WebDriverBy::id('ddlAdTagGroupAndAdTags')));
$filterOptions = $adTagFilter->getOptions();

foreach($filterOptions as $option) {
    sleep(1);

    $optionText = $option->getText();
    $adTagFilter->selectByValue($option->getAttribute('value'));
    $runReportButton->click();

    sleep(2);

    $driver->wait()->until(WebDriverExpectedCondition::invisibilityOfElementLocated(WebDriverBy::cssSelector('.blockUI')));

    try {
        $noDataMessage = $driver->findElement(WebDriverBy::cssSelector('.reportData .noImpressionDomainsDataContainer'));
        if ($noDataMessage->isDisplayed()) {
            echo sprintf("No impression data report for: %s\n", $optionText);
            continue;
        }
    } catch (NoSuchElementException $e) {}

    try {
        $exportButton = $driver->findElement(WebDriverBy::cssSelector(EXPORT_BUTTON_SEL));
        if ($exportButton->isDisplayed()) {
            $driver->wait()->until(WebDriverExpectedCondition::elementToBeClickable(WebDriverBy::cssSelector(EXPORT_BUTTON_SEL)));
            echo sprintf("Downloading impression domains report for: %s\n", $optionText);
            $exportButton->click();
            continue;
        }
    } catch (NoSuchElementException $e) {}

    try {
        $emailField = $driver->findElement(WebDriverBy::name('txtEmail'));
    } catch (NoSuchElementException $e) {
        echo sprintf("Skipping impression domains report for: %s\n", $optionText);
        continue;
    }

    $driver->wait()->until(WebDriverExpectedCondition::elementToBeClickable(WebDriverBy::cssSelector('.sendButton a.button')));

    echo sprintf("Emailing impression domains report for: %s\n", $optionText);

    $emailField
        ->clear()
        ->sendKeys(REPORT_EMAIL)
        ->submit()
    ;
}
