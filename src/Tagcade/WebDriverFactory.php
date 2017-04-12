<?php

namespace Tagcade;

use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Exception\UnknownServerException;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Psr\Log\LoggerInterface;
use Tagcade\Service\Fetcher\Params\PartnerParamInterface;
use Tagcade\Service\WebDriverService;

class WebDriverFactory implements WebDriverFactoryInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var
     */
    private $seleniumServerUrl;

    /**
     * @var PartnerParamInterface
     */
    private $params;
    /**
     * @var array
     */
    private $config;

    public function __construct($seleniumServerUrl = 'http://localhost:4444/wd/hub', LoggerInterface $logger = null)
    {
        $this->logger = $logger;
        $this->seleniumServerUrl = $seleniumServerUrl;
    }

    /**
     * @param array $config
     * @throws \Exception
     */
    public function setConfig(array $config)
    {
        if (!array_key_exists('publisher_id', $config) || !array_key_exists('partner_cname', $config)) {
            throw new \Exception('Missing configuration for either publisher id or partner canonical name');
        }

        $this->config = $config;
    }

    /**
     * @param PartnerParamInterface $params
     */
    public function setParams($params)
    {
        $this->params = $params;
    }


    public function getExistingSession($sessionId)
    {
        if (!is_string($sessionId)) {
            return false;
        }

        $availableSessions = array_map(function (array $session) {
            return $session['id'];
        }, RemoteWebDriver::getAllSessions($this->seleniumServerUrl));

        if (!in_array($sessionId, $availableSessions)) {
            if ($this->logger) {
                $this->logger->error(sprintf("The supplied session id %s does not exist", $sessionId));
            }

            return false;
        }

        $driver = RemoteWebDriver::createBySessionID($sessionId, $this->seleniumServerUrl);

        try {
            // do a check to see if the existing session has window handles
            $driver->getWindowHandles();
        } catch (UnknownServerException $e) {
            if ($this->logger) {
                $this->logger->error(sprintf("Could not connect to the browser window for session id %s, did you close it? We will try to create a new one instead", $sessionId));
            }

            return false;
        }

        return $driver;
    }

    public function getLastSessionId()
    {
        $allSessions = RemoteWebDriver::getAllSessions($this->seleniumServerUrl);
        $lastSession = count($allSessions) < 1 ? [] : current($allSessions);

        return array_key_exists('id', $lastSession) ? $lastSession['id'] : null;
    }

    /**
     * @inheritdoc
     */
    public function getWebDriver($identifier, $rootDownloadDir = null, $subDir = null)
    {
        $this->logger->info(sprintf('identifier value=%s', $identifier));
        if (strpos($identifier, '/') === false && strpos($identifier, '\\') === false && !is_dir($identifier)) {
            $driver = $this->getExistingSession($identifier);

            if ($driver instanceof RemoteWebDriver) {
                $this->logger->info('Using exiting web driver');
                return $driver;
            }

            $this->logger->info(sprintf('Could not create web driver from session %s. Try to clear session and create a new one now', $identifier));
            $this->clearAllSessions();

            $identifier = $rootDownloadDir;
        }

        $this->logger->info(sprintf('Create web driver with identifier %s', $identifier));
        $driver = $this->createWebDriver($identifier, $subDir);

        $sessionId = $driver->getSessionID();

        if ($this->logger) {
            $this->logger->info(sprintf("Session created: %s", $sessionId));
        }

        return $driver;
    }

    /**
     * @inheritdoc
     */
    public function createWebDriver($rootDownloadDir, $subDir = null)
    {
        $chromeOptions = new ChromeOptions();
        $chromeOptions->addArguments([sprintf('user-data-dir=%s/.chrome/profile.%s', $rootDownloadDir, uniqid($prefix = '', $more_entropy = true))]);
        $executionDate = new \DateTime('today');

        $defaultDownloadPath = WebDriverService::getDownloadPath(
            $rootDownloadDir,
            $this->config['publisher_id'],
            $this->config['partner_cname'],
            $executionDate,
            $this->params->getStartDate(),
            $this->params->getEndDate(),
            $this->config['process_id'],
            $subDir
        );

        $chromeOptions->setExperimentalOption('prefs', [
            'download.default_directory' => $defaultDownloadPath,
            'download.prompt_for_download' => false,
            //Turns off download prompt
            'profile.default_content_settings.popups' => 0,
            'profile.content_settings.pattern_pairs.*.multiple-automatic-downloads' => 1,
            'profile.password_manager_enabled' => false,
            'credentials_enable_service' => false
        ]);

        $this->logger->debug(sprintf('Path to store data =%s', $defaultDownloadPath));

        $capabilities = DesiredCapabilities::chrome();
        $capabilities->setCapability(ChromeOptions::CAPABILITY, $chromeOptions);

        $driver = RemoteWebDriver::create($this->seleniumServerUrl, $capabilities);

        return $driver;
    }

    public function clearAllSessions()
    {
        $sessions = RemoteWebDriver::getAllSessions($this->seleniumServerUrl);
        foreach ($sessions as $session) {
            $driver = RemoteWebDriver::createBySessionID($session['id'], $this->seleniumServerUrl);
            try {
                $driver->manage()->deleteAllCookies();
            } catch (\Exception $e) {
                $this->logger->info("Failed to delete cookies for browser");
            }
            $driver->quit();
            $this->logger->info(sprintf("Cleared Session: %s\n", $session['id']));
        }
    }
}