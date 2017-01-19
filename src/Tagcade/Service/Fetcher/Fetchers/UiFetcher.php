<?php

namespace Tagcade\Service\Fetcher\Fetchers;

use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverDimension;
use Facebook\WebDriver\WebDriverPoint;
use Psr\Log\LoggerInterface;
use Tagcade\DataSource\PartnerFetcherInterface;
use Tagcade\DataSource\PartnerParamInterface;
use Tagcade\Service\Fetcher\ApiParameterInterface;
use Tagcade\Service\Fetcher\FetcherInterface;
use Tagcade\Service\Fetcher\Fetchers\Ui\UiFetcherInterface;
use Tagcade\WebDriverFactoryInterface;

class UiFetcher extends BaseFetcher implements FetcherInterface
{
    const TYPE = FetcherInterface::TYPE_UI;

    /** @var array|UiFetcherInterface[]|PartnerFetcherInterface[] */
    protected $uiFetchers;

    /** @var LoggerInterface */
    protected $logger;

    /**
     * UiFetcher constructor.
     * @param array $uiFetchers
     * @param LoggerInterface $logger
     */
    public function __construct(array $uiFetchers, LoggerInterface $logger)
    {
        $this->uiFetchers = [];

        /**@var UiFetcherInterface $uiFetcher */
        foreach ($uiFetchers as $uiFetcher) {
            if (!$uiFetcher instanceof UiFetcherInterface) {
                return;
            }
            $this->uiFetchers [] = $uiFetcher;
        }

        $this->logger = $logger;
    }

    /**
     * @inheritdoc
     */
    public function execute(ApiParameterInterface $parameters)
    {
        /**@var UiFetcherInterface|PartnerFetcherInterface $uiFetcher */
        foreach ($this->uiFetchers as $uiFetcher) {
            if (!$uiFetcher->supportIntegration($parameters)) {
                continue;
            }

            $this->doGetData($uiFetcher, $parameters);
        }
    }

    /**
     * @inheritdoc
     */
    private function doGetData($uiFetcher, ApiParameterInterface $parameter)
    {
        /** @var int publisherId */
        $publisherId = 1;
        /** @var PartnerParamInterface $params */
        $params = null;
        /** @var array $config */
        $config = [];
        /** @var string dataPath */
        $dataPath = '';

        $this->getDataForPublisher($uiFetcher, $publisherId, $params, $config, $dataPath);
    }

    protected function getDataForPublisher($uiFetcher, $publisherId, PartnerParamInterface $params, array $config, $dataPath)
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

            $this->handleGetDataByDateRange($uiFetcher, $params, $driver);

            $this->logger->info(sprintf('Finished getting %s data', $uiFetcher->getName()));
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

    protected function handleGetDataByDateRange($uiFetcher, PartnerParamInterface $params, RemoteWebDriver $driver)
    {
        $uiFetcher->getAllData($params, $driver);
    }
}