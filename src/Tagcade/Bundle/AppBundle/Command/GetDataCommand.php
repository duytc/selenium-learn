<?php

namespace Tagcade\Bundle\AppBundle\Command;

use Facebook\WebDriver\Remote\RemoteWebDriver;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;
use Tagcade\DataSource\PartnerFetcherInterface;
use Tagcade\DataSource\PartnerParams;

abstract class GetDataCommand extends ContainerAwareCommand
{
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
                'Start date (YYYYMMDD) to get report.',
                (new \DateTime('yesterday'))->format('Ymd')
            )
            ->addOption(
                'end-date',
                't',
                InputOption::VALUE_OPTIONAL,
                'End date (YYYYMMDD) to get report.'
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
//            ->addOption(
//                'disable-email',
//                null,
//                InputOption::VALUE_NONE,
//                'If set, no reports will be emailed, this will be skipped'
//            )
            ->addOption(
                'quit-web-driver-after-run',
                null,
                InputOption::VALUE_NONE,
                'If set, webdriver will quit after each run. The session will be clear as well.'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->createLogger();
        $this->createYamlParser();
        $this->createFetcher();

        $configFile = $input->getOption('config-file');

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

        // todo we need to write to unique directories per publisher
        $dataPath = $input->getOption('data-path');
        if ($dataPath == null) {
            $dataPath = $this->getDefaultDataPath();

        }

        $symfonyAppDir = $this->getContainer()->getParameter('kernel.root_dir');
        $dataPath = sprintf('%s/%s', rtrim($symfonyAppDir, '/app'), ltrim($dataPath, './'));
        if (!is_writable($dataPath)) {
            $this->logger->error(sprintf('Cannot write to data-path %s', $dataPath));
            return 1;
        }

        $startDate = $input->getOption('start-date');
        $startDate = $startDate != null ? \DateTime::createFromFormat('Ymd', $startDate) : new \DateTime('yesterday');

        $endDate = $input->getOption('end-date');
        $endDate = $endDate != null ? \DateTime::createFromFormat('Ymd', $endDate) : $startDate;

        $webDriverFactory = $this->getContainer()->get('tagcade.web_driver_factory');
        $forceNewSession = $input->getOption('force-new-session');
        $sessionId = null;
        if ($forceNewSession == false) {
            $sessionId = $webDriverFactory->getLastSessionId();
        }

        $identifier = $sessionId != null ? $sessionId : $dataPath;

        $driver = $webDriverFactory->getWebDriver($identifier);
        $this->logger->info(sprintf('Session ID: %s', $driver->getSessionID()));

        if (!$driver) {
            $this->logger->critical('Cannot proceed without web driver');
            return 1;
        }

        $driver->manage()
            ->timeouts()
            ->implicitlyWait(3)
            ->pageLoadTimeout(10)
        ;

        $params = $this->createParams($config, $startDate, $endDate);

        $this->fetcher->getAllData($params, $driver);

        $driver->wait()->until(function (RemoteWebDriver $driver) {
                return $driver->executeScript("return !!window.jQuery && window.jQuery.active == 0");
            });

        $this->logger->info(sprintf('Finished getting %s data', $this->fetcher->getName()));

        sleep(10); // sleep 10 seconds, to assume that the download is complete.
        // todo check that chrome finished downloading all files before finishing
        if ($input->getOption('quit-web-driver-after-run')) {
            $driver->quit();
        }

        return 0;
    }

    /**
     * @return PartnerFetcherInterface
     */
    protected function createFetcher()
    {
        if (!$this->fetcher instanceof PartnerFetcherInterface) {
            $this->fetcher = $this->getFetcher();
        }

        return $this->getFetcher();
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

    protected function createParams(array $config, \DateTime $startDate, \DateTime $endDate)
    {
        if ($startDate > $endDate) {
            throw new \InvalidargumentException(sprintf('Invalid date range startDate=%s, endDate=%s', $startDate->format('Ymd'), $endDate->format('Ymd')));
        }
        /**
         * todo date should be configurable
         */
        return (new PartnerParams())
            ->setUsername($config['username'])
            ->setPassword($config['password'])
            ->setStartDate($startDate)
            ->setEndDate($endDate)
        ;
    }
} 