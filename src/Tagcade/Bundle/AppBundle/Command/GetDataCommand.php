<?php

namespace Tagcade\Bundle\AppBundle\Command;

use Crypto;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;
use Tagcade\DataSource\PartnerFetcherInterface;
use Tagcade\DataSource\PartnerParamInterface;
use Tagcade\DataSource\PartnerParams;
use Tagcade\Service\Core\TagcadeRestClientInterface;

abstract class GetDataCommand extends ContainerAwareCommand
{
    const DEFAULT_CANONICAL_NAME = null;

    /**
     * @var Yaml
     */
    protected $yaml;
    /**
     * @var LoggerInterface
     */
    protected $logger;
    /**
     * @var PartnerFetcherInterface
     */
    protected $fetcher;

    protected static $REQUIRED_CONFIG_FIELDS = ['username', 'password', 'email', 'publisher_id'];

    protected function configure()
    {
        $this
            ->addOption(
                'start-date',
                'f',
                InputOption::VALUE_OPTIONAL,
                'Start date (YYYY-MM-DD) to get report.',
                (new \DateTime('yesterday'))->format('Y-m-d')
            )
            ->addOption(
                'end-date',
                't',
                InputOption::VALUE_OPTIONAL,
                'End date (YYYY-MM-DD) to get report.'
            )
            ->addOption(
                'data-path',
                null,
                InputOption::VALUE_OPTIONAL,
                'Path to the directory that will store downloaded files'
            )
            ->addOption(
                'force-new-session',
                null,
                InputOption::VALUE_NONE,
                'New session will always be created if this is set. Otherwise, the tool will automatically decide new session or using existing session'
            )
            ->addOption(
                'partner-cname',
                null,
                InputOption::VALUE_OPTIONAL,
                'The default canonical name for the current partner ',
                static::DEFAULT_CANONICAL_NAME
            )
            ->addOption(
                'quit-web-driver-after-run',
                null,
                InputOption::VALUE_NONE,
                'If set, webdriver will quit after each run. The session will be clear as well.'
            )
            ->addOption(
                'config-file',
                'c',
                InputOption::VALUE_OPTIONAL,
                sprintf('Path to the config file. See ./config/%s.yml.dist for an example', static::DEFAULT_CANONICAL_NAME)
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->createLogger();
        $this->createYamlParser();

        $partnerCName = $input->getOption('partner-cname');
        $this->createFetcher($partnerCName);


        // todo we need to write to unique directories per publisher
        $dataPath = $input->getOption('data-path');
        if ($dataPath == null) {
            $dataPath = $this->getDefaultDataPath();
        }

        $symfonyAppDir = $this->getContainer()->getParameter('kernel.root_dir');
        $isRelativeToProjectRootDir = (strpos($dataPath, './') === 0 || strpos($dataPath, '/') !== 0);
        $dataPath = $isRelativeToProjectRootDir ? sprintf('%s/%s', rtrim($symfonyAppDir, '/app'), ltrim($dataPath, './')) : $dataPath;
        if (!is_writable($dataPath)) {
            $this->logger->error(sprintf('Cannot write to data-path %s', $dataPath));
            return 1;
        }

        $startDate = $input->getOption('start-date');
        $startDate = $startDate != null ? \DateTime::createFromFormat('Y-m-d', $startDate) : new \DateTime('yesterday');
        if (!$startDate instanceof \DateTime) {
            throw new \Exception('Invalid start date format. Expect to be YYYY-mm-dd');
        }

        $endDate = $input->getOption('end-date');
        $endDate = $endDate != null ? \DateTime::createFromFormat('Y-m-d', $endDate) : clone $startDate;
        if (!$endDate instanceof \DateTime) {
            throw new \Exception('Invalid end date format. Expect to be YYYY-mm-dd');
        }

        $configFile = $input->getOption('config-file');
        $configs = [];
        if ($configFile !== null) {
            if (!file_exists($configFile)) {
                $this->logger->error(sprintf('config-file %s does not exist', $configFile));
                return 1;
            }

            try {
                $config = $this->yaml->parse(file_get_contents($configFile));
            } catch (ParseException $e) {
                $this->logger->error('Unable to parse the YAML string: %s', $e->getMessage());
                return 1;
            }

            $missingConfigKeys = array_diff_key(array_flip(static::$REQUIRED_CONFIG_FIELDS), $config);
            if (count($missingConfigKeys) > 0) {
                $this->logger->error('Please check that your config has all of the required keys. See ./config/pulsepoint.yml.dist for an example');
                return 1;
            }

            $configs[] = $config;
        } else {
            /** @var TagcadeRestClientInterface $restClient */
            $restClient = $this->getContainer()->get('tagcade_app.rest_client');

            $this->logger->info('Getting list of publishers and their configuration for this partner');
            $configs = $restClient->getPartnerConfigurationForAllPublishers($partnerCName);

            $this->logger->info(sprintf('Found %d publishers associated to this partner', count($configs)));
        }

        $processedPublisherPartner = [];
        foreach($configs as $config) {
            try {
                if (!array_key_exists('publisher_id', $config) && !isset($config['publisher']['id'])) {
                    throw new \Exception('Expect to have publisher_id or publisher object in the config');
                }

                $publisherId = array_key_exists('publisher_id', $config) ? (int)$config['publisher_id'] : (int)$config['publisher']['id'];

                if (empty($config['username']) || empty($config['base64EncryptedPassword'])) {
                    $this->logger->info(sprintf('Invalid credentials for publisher %d, skipping', $publisherId));
                    continue;
                }

                $params = $this->createParams($config, $startDate, $endDate);
                if (array_key_exists($publisherId, $processedPublisherPartner)) {
                    $this->logger->info(sprintf('The publisher %d has been processed.', $publisherId));
                    continue;
                }

                $this->logger->info(sprintf('Getting report for publisher %d, using fetcher %s', $publisherId, $this->fetcher->getName()));
                if (!array_key_exists('publisher_id', $config)) {
                    $config['publisher_id'] = $publisherId;
                }

                if (!array_key_exists('partner_cname', $config)) {
                    $config['partner_cname'] = $partnerCName;
                }

                $this->getDataForPublisher($input, $publisherId, $params, $config, $dataPath);
                $processedPublisherPartner[$publisherId] = true;
            }
            catch(\CannotPerformOperationException $ce) {
                $this->logger->critical('Decryption error. Please make sure you have mycrypt installed for defuse encryption and the credentials are correct');
                $this->logger->critical($ce->getMessage());
            }
            catch(\Exception $e) {
                $message = $e->getMessage() ? $e->getMessage() : $e->getTraceAsString();
                $this->logger->critical($message);
            }
        }

        return 0;
    }

    protected function getDataForPublisher(InputInterface $input, $publisherId, PartnerParamInterface $params, array $config, $dataPath)
    {
        $config['publisher_id'] = $publisherId;

        $webDriverFactory = $this->getContainer()->get('tagcade.web_driver_factory');
        $webDriverFactory->setConfig($config);
        $webDriverFactory->setParams($params);

        $forceNewSession = $input->getOption('force-new-session');
        $sessionId = null;
        if ($forceNewSession == false) {
            $sessionId = $webDriverFactory->getLastSessionId();
        }
        else {
            $webDriverFactory->clearAllSessions();
        }

        $identifier = $sessionId != null ? $sessionId : $dataPath;

        $this->logger->info(sprintf('Creating web driver with identifier param %s', $identifier));
        $driver = $webDriverFactory->getWebDriver($identifier, $dataPath);

        if (!$driver) {
            $this->logger->critical('Failed to create web driver from sessio');
            return 1;
        }

        try {

            $driver->manage()
                ->timeouts()
                ->implicitlyWait(3)
                ->pageLoadTimeout(10)
            ;

            $driver->manage()->window()->setPosition(new \Facebook\WebDriver\WebDriverPoint(0, 0));
            // todo make this configurable
            $driver->manage()->window()->setSize(new \Facebook\WebDriver\WebDriverDimension(1920, 1080));

            $this->logger->info('Fetcher starts to get data');
            $this->handleGetDataByDateRange($params, $driver);

            $this->logger->info(sprintf('Finished getting %s data', $this->fetcher->getName()));

            sleep(10); // sleep 10 seconds, to assume that the download is complete.
        }
        catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }

        // todo check that chrome finished downloading all files before finishing
        if ($input->getOption('quit-web-driver-after-run')) {
            $driver->quit();
        }

        return 0;
    }

    /**
     * @param $partnerCName
     * @return PartnerFetcherInterface
     */
    protected function createFetcher($partnerCName)
    {
        if (!$this->fetcher instanceof PartnerFetcherInterface) {
            $this->fetcher = $this->getFetcher();
            $this->fetcher->setName($partnerCName);
        }

        return $this->getFetcher();
    }

    protected function handleGetDataByDateRange(PartnerParamInterface $params, RemoteWebDriver $driver)
    {
        $this->fetcher->getAllData($params, $driver);
    }

    protected abstract function getFetcher();

    /**
     * @return LoggerInterface|\Symfony\Bridge\Monolog\Logger
     */
    protected function createLogger()
    {
        if ($this->logger == null) {
            $this->logger = $this->getContainer()->get('logger');
        }

        return $this->logger;
    }

    /**
     * @return Yaml
     */
    protected function createYamlParser()
    {
        if ($this->yaml == null) {
            $this->yaml = $this->getContainer()->get('yaml');
        }
        return $this->yaml;
    }

    protected function getDefaultDataPath()
    {
        return  $this->getContainer()->getParameter('tagcade.default_data_path');
    }

    /**
     * @param array $config
     * @param \DateTime $startDate
     * @param \DateTime $endDate
     * @return PartnerParamInterface
     * @throws \CannotPerformOperationException
     * @throws \InvalidCiphertextException
     * @throws \Exception
     */
    protected function createParams(array $config, \DateTime $startDate, \DateTime $endDate)
    {
        if ($startDate > $endDate) {
            throw new \InvalidargumentException(sprintf('Invalid date range startDate=%s, endDate=%s', $startDate->format('Ymd'), $endDate->format('Ymd')));
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
            $password = Crypto::decrypt($encryptedPassword, $decryptKey);
        }
        else {
            $password = $config['password'];
        }

        /**
         * todo date should be configurable
         */
        return (new PartnerParams())
            ->setUsername($config['username'])
            ->setPassword($password)
            ->setStartDate(clone $startDate)
            ->setEndDate(clone $endDate)
        ;
    }

    protected function getEncryptionKey($uuid)
    {
        $uuid = preg_replace('[\-]', '', $uuid);
        return substr($uuid, 0, 16);
    }
} 