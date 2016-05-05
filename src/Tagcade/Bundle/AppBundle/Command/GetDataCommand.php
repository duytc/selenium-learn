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
            ->addOption(
                'partner-cname',
                null,
                InputOption::VALUE_OPTIONAL,
                'The default canonical name for the current partner '
            )
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
        $this->createFetcher();

        $partnerCName = $input->getOption('partner-cname');

        if ($partnerCName === null) {
            $partnerCName = static::DEFAULT_CANONICAL_NAME;
        }

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
        $startDate = $startDate != null ? \DateTime::createFromFormat('Ymd', $startDate) : new \DateTime('yesterday');

        $endDate = $input->getOption('end-date');
        $endDate = $endDate != null ? \DateTime::createFromFormat('Ymd', $endDate) : $startDate;

        /** @var TagcadeRestClientInterface $restClient */
        $restClient = $this->getContainer()->get('tagcade_app.rest_client');
        $configs = $restClient->getListPublisherWorkWithPartner($partnerCName);
        
        foreach($configs as $config) {
            $params = $this->createParams($config, $startDate, $endDate);

            $publisherId = intval($config['publisher']['id']);
            $this->getDataForPublisher($input, $publisherId, $params, $config, $dataPath);
        }

        return 0;
    }

    protected function getDataForPublisher(InputInterface $input, $publisherId, PartnerParams $params, array $config, $dataPath)
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

        $this->fetcher->getAllData($params, $driver);

        $this->logger->info(sprintf('Finished getting %s data', $this->fetcher->getName()));

        sleep(10); // sleep 10 seconds, to assume that the download is complete.
        // todo check that chrome finished downloading all files before finishing
        if ($input->getOption('quit-web-driver-after-run')) {
            $driver->quit();
        }
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

        // decrypt the hashed password
        $password = Crypto::decrypt($config['password'], $config['publisher']['uuid']);

        /**
         * todo date should be configurable
         */
        return (new PartnerParams())
            ->setUsername($config['username'])
            ->setPassword($password)
            ->setStartDate($startDate)
            ->setEndDate($endDate)
        ;
    }
} 