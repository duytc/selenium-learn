<?php

namespace Tagcade\Service\Fetcher\Fetchers\Ui;

use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverDimension;
use Facebook\WebDriver\WebDriverPoint;
use Psr\Log\LoggerInterface;
use Tagcade\DataSource\PartnerFetcherInterface;
use Tagcade\DataSource\PartnerParamInterface;
use Tagcade\Service\Fetcher\ApiParameterInterface;
use Tagcade\Service\Fetcher\Fetchers\ApiFetcher;
use Tagcade\WebDriverFactoryInterface;

abstract class AbstractUiFetcher implements UiFetcherInterface
{
    const INTEGRATION_C_NAME = null;

    /**
     * @var LoggerInterface
     */
    protected $logger;
    /**
     * @var PartnerFetcherInterface
     */
    protected $fetcher;

    public function __construct(LoggerInterface $logger, PartnerFetcherInterface $fetcher)
    {
        $this->logger = $logger;
        $this->fetcher = $fetcher;
    }

    /**
     * @inheritdoc
     */
    function supportIntegration(ApiParameterInterface $parameter)
    {
        $allParams = $parameter->getParams();
        $type = $allParams['type'];
        $integrationCName = $parameter->getIntegrationCName();

        return (($integrationCName == self::INTEGRATION_C_NAME) && ($type == ApiFetcher::TYPE_UI));
    }

    /**
     * @inheritdoc
     */
    function doGetData(ApiParameterInterface $parameter)
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

            $this->logger->info(sprintf('Finished getting %s data', $this->fetcher->getName()));
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

    protected function handleGetDataByDateRange(PartnerParamInterface $params, RemoteWebDriver $driver)
    {
        $this->fetcher->getAllData($params, $driver);
    }
}