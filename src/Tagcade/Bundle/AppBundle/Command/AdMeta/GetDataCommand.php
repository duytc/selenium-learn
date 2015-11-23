<?php

namespace Tagcade\Bundle\AppBundle\Command\AdMeta;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;
use anlutro\cURL\cURL;
use Tagcade\DataSource\AdMeta\Api;

class GetDataCommand extends ContainerAwareCommand {
    private static $requiredConfigFields = ['username', 'password', 'publisher_id'];

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
     * @param null|string $defaultDataPath
     * @param Yaml $yaml
     * @param LoggerInterface $logger
     */
    public function __construct($defaultDataPath, Yaml $yaml, LoggerInterface $logger)
    {
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
            ->setName('tagcade:admeta:get-data')
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
                'proxy',
                null,
                InputOption::VALUE_REQUIRED,
                'Send all traffic through a proxy. This is useful for development i.e socks5://127.0.0.1:8888'
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

        // todo use symfony DI
        $curl = new cURL;

        if ($input->getOption('proxy')) {
            $curl->setDefaultOptions(
                [
                    CURLOPT_PROXY => $input->getOption('proxy')
                ]
            );
        }

        // todo use symfony DI
        $api = new Api($config['username'], $config['password'], $curl, $this->logger);

        $dataFile = $this->getUniqueFilePath(sprintf('%s/reports.xml', $this->defaultDataPath));
        touch($dataFile);
        file_put_contents($dataFile, $api->getReports());
    }

    /**
     * When this tool is run multiple tools we want to avoid overwriting existing files
     * If the file "reports.xml" exists, this function will return "reports (1).xml"
     *
     * @param $filePath
     * @return string
     */
    protected function getUniqueFilePath($filePath)
    {
        if (!file_exists($filePath)) {
            return $filePath;
        }

        $dotPosition = strrpos($filePath, '.');
        $ext = null;

        if ($dotPosition) {
            $name = substr($filePath, 0, $dotPosition);
            $ext = substr($filePath, $dotPosition);
        } else {
            $name = $filePath;
        }

        $counter = 1;

        do {
            $newName = sprintf('%s (%d)', $name, $counter);

            if ($ext) {
                $newName .= sprintf('.%s', $ext);
            }

            $counter++;
        } while (file_exists($newName));

        return $newName;
    }
}