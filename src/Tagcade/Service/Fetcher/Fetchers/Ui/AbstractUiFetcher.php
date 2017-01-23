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
     * @param PartnerFetcherInterface $partnerFetcher
     */
    public function __construct(LoggerInterface $logger, WebDriverFactoryInterface $webDriverFactory, $symfonyAppDir, $defaultDataPath, PartnerFetcherInterface $partnerFetcher)
    {
        $this->logger = $logger;
        $this->webDriverFactory = $webDriverFactory;
        $this->symfonyAppDir = $symfonyAppDir;
        $this->defaultDataPath = $defaultDataPath;
        $this->partnerFetcher = $partnerFetcher;
    }

    /**
     * @inheritdoc
     */
    public function supportIntegration(ApiParameterInterface $parameter)
    {
        return $parameter->getIntegrationCName() == $this->integrationCName;
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

        $dataPath = $this->defaultDataPath;
        $isRelativeToProjectRootDir = (strpos($dataPath, './') === 0 || strpos($dataPath, '/') !== 0);
        $dataPath = $isRelativeToProjectRootDir ? sprintf('%s/%s', rtrim($this->symfonyAppDir, '/app'), ltrim($dataPath, './')) : $dataPath;
        if (!is_writable($dataPath)) {
            $this->logger->error(sprintf('Cannot write to data-path %s', $dataPath));
            return 1;
        }

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

        /** @var \DateTime $startDate */
        if (!array_key_exists('startDate', $config)) {
            $startDate = new \DateTime('yesterday');
        } else {
            $startDate = date_create($config['startDate']);
        }

        /** @var \DateTime $endDate */
        if (!array_key_exists('endDate', $config)) {
            $endDate = new \DateTime('yesterday');
        } else {
            $endDate = date_create($config['endDate']);
        }

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
        $processId = getmypid();
        $config = [
            'publisher_id' => $publisherId,
            'partner_cname' => $integrationCName,
            'force-new-session' => true, // TODO: get from params
            'quit-web-driver-after-run' => true, // TODO: get from params
            'process_id' => $processId
        ];

        $this->webDriverFactory->setConfig($config);
        $this->webDriverFactory->setParams($params);

        $forceNewSession = $config['force-new-session'];
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
        $quitWebDriverAfterRun = $config['quit-web-driver-after-run'];
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
     * @param string $integrationCName
     * @return self
     */
    public function setIntegrationCName($integrationCName)
    {
        $this->integrationCName = $integrationCName;
        return $this;
    }
}