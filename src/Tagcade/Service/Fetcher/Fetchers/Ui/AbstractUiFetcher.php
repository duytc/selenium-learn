<?php

namespace Tagcade\Service\Fetcher\Fetchers\Ui;

use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverDimension;
use Facebook\WebDriver\WebDriverPoint;
use Psr\Log\LoggerInterface;
use Tagcade\DataSource\PartnerFetcherInterface;
use Tagcade\DataSource\PartnerParamInterface;
use Tagcade\Service\Fetcher\ApiParameterInterface;
use Tagcade\WebDriverFactoryInterface;

abstract class AbstractUiFetcher implements UiFetcherInterface
{
    /** @var LoggerInterface */
    protected $logger;

    /** @var PartnerFetcherInterface */
    protected $partnerFetcher;

    /**
     * @param LoggerInterface $logger
     * @param PartnerFetcherInterface $partnerFetcher
     */
    public function __construct(LoggerInterface $logger, PartnerFetcherInterface $partnerFetcher)
    {
        $this->logger = $logger;
        $this->partnerFetcher = $partnerFetcher;
    }

    /**
     * @inheritdoc
     */
    public function supportIntegration(ApiParameterInterface $parameter)
    {
        return $parameter->getIntegrationCName() == $this->getIntegrationCName();
    }

    /**
     * @inheritdoc
     */
    public function doGetData(ApiParameterInterface $parameter)
    {
        /** @var int publisherId */
        $publisherId = 1;
        /** @var PartnerParamInterface $params */
        $params = null;
        /** @var array $config */
        $config = [];
        /** @var string dataPath */
        $dataPath = '';

        $this->getDataForPublisher($publisherId, $params, $config, $dataPath);
    }

    /**
     * get Data For Publisher
     *
     * @param $publisherId
     * @param PartnerParamInterface $params
     * @param array $config
     * @param $dataPath
     * @return int
     */
    protected function getDataForPublisher($publisherId, PartnerParamInterface $params, array $config, $dataPath)
    {
        $config['publisher_id'] = $publisherId;

        /** @var WebDriverFactoryInterface $webDriverFactory */
        $webDriverFactory = null;
        $webDriverFactory->setConfig($config);
        $webDriverFactory->setParams($params);


        $forceNewSession = false;
        $sessionId = null;

        if ($forceNewSession == false) {
            $sessionId = $webDriverFactory->getLastSessionId();
        } else {
            $webDriverFactory->clearAllSessions();
        }

        $identifier = $sessionId != null ? $sessionId : $dataPath;

        $this->logger->info(sprintf('Creating web driver with identifier param %s', $identifier));
        $driver = $webDriverFactory->getWebDriver($identifier, $dataPath);
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

            $this->handleGetDataByDateRange($params, $driver);

            $this->logger->info(sprintf('Finished getting %s data', $this->partnerFetcher->getName()));
        } catch (\Exception $e) {
            $message = $e->getMessage() ? $e->getMessage() : $e->getTraceAsString();
            $this->logger->critical($message);

        }

        // todo check that chrome finished downloading all files before finishing
        $quitWebDriverAfterRun = true;
        if ($quitWebDriverAfterRun) {
            $driver->quit();
        }

        return 0;
    }

    /**
     * handle Get Data By DateRange
     *
     * @param PartnerParamInterface $params
     * @param RemoteWebDriver $driver
     */
    protected function handleGetDataByDateRange(PartnerParamInterface $params, RemoteWebDriver $driver)
    {
        $this->partnerFetcher->getAllData($params, $driver);
    }

    /**
     * @return string
     */
    public abstract function getIntegrationCName();
}