<?php

namespace Tagcade\Service\Fetcher\Fetchers\Ui;

use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverDimension;
use Facebook\WebDriver\WebDriverPoint;
use Psr\Log\LoggerInterface;
use Tagcade\DataSource\PartnerFetcherInterface;
use Tagcade\DataSource\PartnerParamInterface;
use Tagcade\DataSource\PartnerParams;
use Tagcade\Service\Fetcher\ApiParameterInterface;
use Tagcade\WebDriverFactoryInterface;

abstract class AbstractUiFetcher implements UiFetcherInterface
{
    /** @var LoggerInterface */
    protected $logger;

    /** @var PartnerFetcherInterface */
    protected $partnerFetcher;

    /** @var WebDriverFactoryInterface */
    protected $webDriverFactory;

    /**
     * @param LoggerInterface $logger
     * @param PartnerFetcherInterface $partnerFetcher
     * @param WebDriverFactoryInterface $webDriverFactory
     */
    public function __construct(LoggerInterface $logger, WebDriverFactoryInterface $webDriverFactory, PartnerFetcherInterface $partnerFetcher)
    {
        $this->logger = $logger;
        $this->webDriverFactory = $webDriverFactory;
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
        $publisherId = $parameter->getPublisherId();
        /** @var string $integrationCName */
        $integrationCName = $parameter->getIntegrationCName();

        $params = $parameter->getParams();
        if (!is_array($params)) {
            return false;
        }

        /** @var PartnerParamInterface $partnerParams */
        $partnerParams = $this->createParams($params);

        /** @var string dataPath */
        $dataPath = '';

        return $this->getDataForPublisher($publisherId, $integrationCName, $partnerParams, $dataPath);
    }

    /**
     * create PartnerParams from configs
     *
     * @param array $config
     * @return PartnerParamInterface
     * @throws \CannotPerformOperationException
     * @throws \InvalidCiphertextException
     * @throws \Exception
     */
    protected function createParams(array $config)
    {
        /** @var string $username */
        $username = $config['username'];

        /** @var \DateTime */
        $startDate = date_create($config['startDate']);

        /** @var \DateTime */
        $endDate = date_create($config['endDate']);

        if ($startDate > $endDate) {
            throw new \InvalidArgumentException(sprintf('Invalid date range startDate=%s, endDate=%s', $startDate->format('Ymd'), $endDate->format('Ymd')));
        }

        if (!array_key_exists('base64EncryptedPassword', $config) && !array_key_exists('password', $config)) {
            throw new \Exception('Invalid configuration. Not found password or base64EncryptedPassword in the configuration');
        }

        if (array_key_exists('base64EncryptedPassword', $config) && !isset($config['publisher']['uuid'])) {
            throw new \Exception('Missing key to decrypt publisher password');
        }

        if (array_key_exists('base64EncryptedPassword', $config)) {
            // decrypt the hashed password
            $base64EncryptedPassword = $config['base64EncryptedPassword'];
            $encryptedPassword = base64_decode($base64EncryptedPassword);

            $decryptKey = $this->getEncryptionKey($config['publisher']['uuid']);
            $password = \Crypto::Decrypt($encryptedPassword, $decryptKey);
        } else {
            $password = $config['password'];
        }

        /**
         * todo date should be configurable
         */
        return (new PartnerParams())
            ->setUsername($username)
            ->setPassword($password)
            ->setStartDate(clone $startDate)
            ->setEndDate(clone $endDate)
            ->setConfig($config);
    }

    /**
     * get Encryption Key
     *
     * @param $uuid
     * @return string
     */
    protected function getEncryptionKey($uuid)
    {
        $uuid = preg_replace('[\-]', '', $uuid);
        return substr($uuid, 0, 16);
    }

    /**
     * get Data For Publisher
     *
     * @param int $publisherId
     * @param string $integrationCName
     * @param PartnerParamInterface $params
     * @param string $dataPath
     * @return int
     */
    protected function getDataForPublisher($publisherId, $integrationCName, PartnerParamInterface $params, $dataPath)
    {
        $config = [
            'publisher_id' => $publisherId,
            'partner_cname' => $integrationCName
        ];

        $this->webDriverFactory->setConfig($config);
        $this->webDriverFactory->setParams($params);

        $forceNewSession = false;
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