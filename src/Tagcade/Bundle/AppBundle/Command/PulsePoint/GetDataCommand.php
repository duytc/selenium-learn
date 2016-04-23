<?php

namespace Tagcade\Bundle\AppBundle\Command\PulsePoint;

use DateTime;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;
use Tagcade\DataSource\PulsePoint\TaskFactoryInterface;

class GetDataCommand extends ContainerAwareCommand
{
    private static $requiredConfigFields = ['username', 'password', 'email', 'publisher_id'];

    /**
     * @var TaskFactoryInterface
     */
    private $pulsepoint;
    /**
     * @var string
     */
    private $defaultDataPath;
    /**
     * @var Yaml
     */
    private $yaml;
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param TaskFactoryInterface $pulsepoint
     * @param string $defaultDataPath
     * @param Yaml $yaml
     * @param LoggerInterface $logger
     */
    public function __construct(TaskFactoryInterface $pulsepoint, $defaultDataPath, Yaml $yaml, LoggerInterface $logger)
    {
        $this->pulsepoint = $pulsepoint;
        // todo refactor common code to base class
        $this->defaultDataPath = $defaultDataPath;
        $this->yaml = $yaml;
        $this->logger = $logger;

        // important to call the parent constructor
        // important to call it at the end, otherwise the above parameters will not be initialized yet
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('tagcade:pulsepoint:get-data')
            ->addOption(
                'config-file',
                'c',
                InputOption::VALUE_REQUIRED,
                'Path to the config file. See ./config/pulsepoint.yml.dist for an example'
            )
            ->addOption(
                'data-path',
                null,
                InputOption::VALUE_REQUIRED,
                'Path to the directory that will store downloaded files',
                $this->defaultDataPath
            )
            ->addOption(
                'session-id',
                null,
                InputOption::VALUE_REQUIRED,
                'This allows you to reuse an existing selenium session. This is useful for development. Otherwise a new one is created every time.'
            )
            ->addOption(
                'disable-email',
                null,
                InputOption::VALUE_NONE,
                'If set, no reports will be emailed, this will be skipped'
            )
            ->addOption(
                'quit-web-driver-after-run',
                null,
                InputOption::VALUE_NONE,
                'If set, webdriver will quit after each run'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
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

        $missingConfigKeys = array_diff_key(array_flip(static::$requiredConfigFields), $config);

        if (count($missingConfigKeys) > 0) {
            $this->logger->error('Please check that your config has all of the required keys. See ./config/pulsepoint.yml.dist for an example');
            return 1;
        }

        // todo we need to write to unique directories per publisher
        $dataPath = $input->getOption('data-path');

        if (!is_writable($dataPath)) {
            $this->logger->error(sprintf('Cannot write to data-path %s', $dataPath));
            return 1;
        }

        $webDriverFactory = $this->pulsepoint->getWebDriverFactory();

        $sessionId = $input->getOption('session-id');

        // todo we could refactor this so that these factories use symfony DI
        if ($input->getOption('session-id')) {
            $driver = $webDriverFactory->getExistingSession($sessionId);
        } else {
            $driver = $webDriverFactory->getWebDriver($dataPath);
        }

        if (!$driver) {
            $this->logger->critical('Cannot proceed without web driver');
            return 1;
        }

        $driver->manage()
            ->timeouts()
            ->implicitlyWait(3)
            ->pageLoadTimeout(10)
        ;

        $params = $this->pulsepoint->createParams(
            $config['username'],
            $config['password'],
            $config['email'],
            /**
             * todo date should be configurable
             */
            new DateTime('yesterday')
        );

        if ($input->getOption('disable-email') === false) {
            $this->logger->info('Disabling email');
            $params->setReceiveReportsByEmail(false);
        };

        $this->pulsepoint->getAllData($params, $driver);

        $this->logger->info('Finished getting pulsepoint data');

        // todo check that chrome finished downloading all files before finishing

        if ($input->getOption('quit-web-driver-after-run')) {
            $driver->quit();
        }

        return 0;
    }
}