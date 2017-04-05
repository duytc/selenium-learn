<?php

namespace Tagcade\Bundle\AppBundle\Command;

use Monolog\Handler\StreamHandler;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Tagcade\Service\Integration\Config;
use Tagcade\Service\Integration\IntegrationManagerInterface;
use Tagcade\Service\Integration\Integrations\IntegrationInterface;

class ExecuteIntegrationJobCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('tc:unified-report-fetcher:execute:integration:job')
            ->addArgument('integrationConfigFile', InputOption::VALUE_REQUIRED, 'integrationConfigFile');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $logger = $this->getContainer()->get('logger');
        $logger->pushHandler(new StreamHandler("php://stderr", \Monolog\Logger::DEBUG));
        $logger->info('starting integration test');

        $tempFileDir = $this->getContainer()->getParameter('integration_temp_file_dir');
        $integrationConfigFile = $input->getArgument('integrationConfigFile');
        $configFile = $tempFileDir . '/' . $integrationConfigFile;
        $rawConfig = json_decode(file_get_contents($configFile), true);

        if (!file_exists($configFile)) {
            echo $configFile . " does not exist\n";
            exit(1);
        }

        if (json_last_error() !== JSON_ERROR_NONE) {
            echo "json config file has errors";
            exit(1);
        }

        $config = new Config($rawConfig['publisherId'], $rawConfig['integrationCName'], $rawConfig['dataSourceId'], json_decode($rawConfig['params'], true), json_decode($rawConfig['backFill'], true));

        /** @var IntegrationManagerInterface $fetcherManager */
        $fetcherManager = $this->getContainer()->get('tagcade.service.integration.integration_manager');

        /** @var IntegrationInterface $integration */
        $integration = $fetcherManager->getIntegration($config);

        $integration->run($config);

        return 0;
    }
}