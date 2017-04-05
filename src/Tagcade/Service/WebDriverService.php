<?php

namespace Tagcade\Service;

use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverDimension;
use Facebook\WebDriver\WebDriverPoint;
use Psr\Log\LoggerInterface;
use Tagcade\Service\Fetcher\PartnerFetcherInterface;
use Tagcade\Service\Fetcher\PartnerParamInterface;
use Tagcade\WebDriverFactoryInterface;

class WebDriverService implements WebDriverServiceInterface
{
    /** @var LoggerInterface */
    protected $logger;

    /** @var WebDriverFactoryInterface */
    protected $webDriverFactory;

    /** @var string */
    protected $symfonyAppDir;

    /** @var string */
    protected $defaultDataPath;

    /** @var string */
    protected $integrationCName;

    /**
     * @param LoggerInterface $logger
     * @param WebDriverFactoryInterface $webDriverFactory
     * @param string $defaultDataPath
     */
    public function __construct(LoggerInterface $logger, WebDriverFactoryInterface $webDriverFactory, $symfonyAppDir, $defaultDataPath)
    {
        $this->logger = $logger;
        $this->webDriverFactory = $webDriverFactory;
        $this->symfonyAppDir = $symfonyAppDir;
        $this->defaultDataPath = $defaultDataPath;
    }

    /**
     * @inheritdoc
     */
    public function doGetData(PartnerFetcherInterface $partnerFetcher, PartnerParamInterface $partnerParams)
    {
        $dataPath = $this->defaultDataPath;
        $isRelativeToProjectRootDir = (strpos($dataPath, './') === 0 || strpos($dataPath, '/') !== 0);
        $dataPath = $isRelativeToProjectRootDir ? sprintf('%s/%s', rtrim($this->symfonyAppDir, '/app'), ltrim($dataPath, './')) : $dataPath;
        if (!is_writable($dataPath)) {
            $this->logger->error(sprintf('Cannot write to data-path %s', $dataPath));
            return 1;
        }

        return $this->getDataForPublisher($partnerFetcher, $partnerParams, $dataPath);
    }

    /**
     * get Data For Publisher
     *
     * @param PartnerFetcherInterface $partnerFetcher
     * @param PartnerParamInterface $params
     * @param string $dataPath
     * @return int
     */
    protected function getDataForPublisher(PartnerFetcherInterface $partnerFetcher, PartnerParamInterface $params, $dataPath)
    {
        $webDriverConfig = [
            'publisher_id' => $params->getPublisherId(),
            'partner_cname' => $params->getIntegrationCName(),
            'force-new-session' => true, // TODO: get from params
            'quit-web-driver-after-run' => true, // TODO: get from params
            'process_id' => $params->getProcessId()
        ];

        $this->webDriverFactory->setConfig($webDriverConfig);
        $this->webDriverFactory->setParams($params);

        $forceNewSession = $webDriverConfig['force-new-session'];
        $sessionId = null;

        if ($forceNewSession == false) {
            $sessionId = $this->webDriverFactory->getLastSessionId();
        } else {
            $this->webDriverFactory->clearAllSessions();
        }

        $identifier = $sessionId != null ? $sessionId : $dataPath;

        $this->logger->info(sprintf('Creating web driver with identifier param %s', $identifier));
        $driver = $this->webDriverFactory->getWebDriver($identifier, $dataPath);
        // you could clear cache and cookies here if using the same profile

        if (!$driver) {
            $this->logger->critical('Failed to create web driver from session');
            return 1;
        }

        try {
            $driver->manage()
                ->timeouts()
                ->implicitlyWait(3)
                ->pageLoadTimeout(10);

            $driver->manage()->window()->setPosition(new WebDriverPoint(0, 0));
            // todo make this configurable
            $driver->manage()->window()->setSize(new WebDriverDimension(1920, 1080));

            $this->logger->info('Fetcher starts to get data');

            $this->handleGetDataByDateRange($partnerFetcher, $params, $driver);

            $this->logger->info(sprintf('Finished getting %s data', get_class($partnerFetcher)));
        } catch (\Exception $e) {
            $message = $e->getMessage() ? $e->getMessage() : $e->getTraceAsString();
            $this->logger->critical($message);
        }

        // todo check that chrome finished downloading all files before finishing
        $quitWebDriverAfterRun = $webDriverConfig['quit-web-driver-after-run'];
        if ($quitWebDriverAfterRun) {
            $driver->quit();
        }

        return 0;
    }

    /**
     * handle Get Data By DateRange
     *
     * @param PartnerFetcherInterface $partnerFetcher
     * @param PartnerParamInterface $params
     * @param RemoteWebDriver $driver
     */
    protected function handleGetDataByDateRange(PartnerFetcherInterface $partnerFetcher, PartnerParamInterface $params, RemoteWebDriver $driver)
    {
        $partnerFetcher->getAllData($params, $driver);
    }
}