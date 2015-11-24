<?php

namespace Tagcade;

use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Exception\UnknownServerException;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Psr\Log\LoggerInterface;

class WebDriverFactory implements WebDriverFactoryInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(LoggerInterface $logger = null)
    {
        $this->logger = $logger;
    }
    
    public function getExistingSession($sessionId)
    {
        if (!is_string($sessionId)) {
            return false;
        }

        $availableSessions = array_map(function(array $session) {
            return $session['id'];
        }, RemoteWebDriver::getAllSessions());

        if (!in_array($sessionId, $availableSessions)) {
            if ($this->logger) {
                $this->logger->error(sprintf("The supplied session id %s does not exist", $sessionId));
            }

            return false;
        }

        $driver = RemoteWebDriver::createBySessionID($sessionId);

        try {
            // do a check to see if the existing session has window handles
            $driver->getWindowHandles();
        } catch (UnknownServerException $e) {
            if ($this->logger) {
                $this->logger->error(sprintf("Could not connect to the browser window for session id %s, did you close it? You can run tools/clear-all-sessions.php to reset", $sessionId));
            }

            return false;
        }

        return $driver;
    }

    public function getWebDriver($dataPath)
    {
        $driver = $this->createWebDriver($dataPath);

        $sessionId = $driver->getSessionID();

        if ($this->logger) {
            $this->logger->info(sprintf("Session created: %s", $sessionId));
        }

        return $driver;
    }

    public function createWebDriver($dataPath)
    {
        $chromeOptions = new ChromeOptions();
        $chromeOptions->addArguments([sprintf('user-data-dir=%s/.chrome/profile', $dataPath)]);
        $chromeOptions->setExperimentalOption('prefs', [
            'download.default_directory' => $dataPath . '/downloads',
        ]);

        $capabilities = DesiredCapabilities::chrome();
        $capabilities->setCapability(ChromeOptions::CAPABILITY, $chromeOptions);

        $driver = RemoteWebDriver::create('http://localhost:4444/wd/hub', $capabilities);

        return $driver;
    }
}