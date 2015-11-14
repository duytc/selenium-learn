<?php

namespace Tagcade;

use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Exception\UnknownServerException;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Psr\Log\LoggerInterface;

class WebDriverFactory
{
    const SESSION_FILE = '.session';

    /**
     * @param array $options
     * @param LoggerInterface $logger
     * @return bool|RemoteWebDriver
     */
    public static function getExistingSession(array $options, LoggerInterface $logger = null)
    {
        $sessionId = null;
        $sessionFile = $options['session-file'];

        if (isset($options['session-id'])) {
            $sessionId = $options['session-id'];

            if ($logger) {
                $logger->info(sprintf("Using existing session: %s", $sessionId));
            }
        } else if (file_exists($sessionFile)) {
            $sessionId = file_get_contents($sessionFile);

            if ($logger) {
                $logger->info(sprintf("Using existing session from saved file: %s", $sessionId));
            }
        }

        if (!$sessionId) {
            return false;
        }

        $availableSessions = array_map(function(array $session) {
            return $session['id'];
        }, RemoteWebDriver::getAllSessions());

        if (!in_array($sessionId, $availableSessions)) {
            if ($logger) {
                $logger->error(sprintf("The supplied session id %s does not exist", $sessionId));
            }

            return false;
        }

        $driver = RemoteWebDriver::createBySessionID($sessionId);

        try {
            // do a check to see if the existing session has window handles
            $driver->getWindowHandles();
        } catch (UnknownServerException $e) {
            if ($logger) {
                $logger->error(sprintf("Could not connect to the browser window for session id %s, did you close it?", $sessionId));
            }

            return false;
        }

        return $driver;
    }

    public static function getWebDriver(array $options, LoggerInterface $logger = null)
    {
        if ('dev' == $options['env']) {
            $driver = static::getExistingSession($options, $logger);

            if ($driver) {
                return $driver;
            }

            unset($driver);
        }

        $chromeOptions = new ChromeOptions();
        $chromeOptions->addArguments([sprintf('user-data-dir=%s/chrome/profile', $options['data-path'])]);
        $chromeOptions->setExperimentalOption('prefs', [
            'download.default_directory' => $options['data-path'] . '/downloads',
        ]);

        $capabilities = DesiredCapabilities::chrome();
        $capabilities->setCapability(ChromeOptions::CAPABILITY, $chromeOptions);

        $driver = RemoteWebDriver::create('http://localhost:4444/wd/hub', $capabilities);

        if ('dev' == $options['env']) {
            $sessionId = $driver->getSessionID();

            if ($logger) {
                $logger->info(sprintf("Session created: %s", $sessionId));
            }

            file_put_contents($options['session-file'], $sessionId);
        }

        return $driver;
    }
}