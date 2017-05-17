<?php

namespace Tagcade\Bundle\AppBundle\Command;

use Exception;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Tagcade\Exception\LoginFailException;
use Tagcade\Exception\RuntimeException;
use Tagcade\Service\Integration\Config;
use Tagcade\Service\Integration\IntegrationManagerInterface;
use Tagcade\Service\Integration\Integrations\IntegrationInterface;

class ExecuteIntegrationJobCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('tc:unified-report-fetcher:integration:run')
            ->setDescription('[Internal used by worker only] Run an integration using the supplied configuration')
            ->addArgument('integrationConfigFile', InputOption::VALUE_REQUIRED, 'temporarily file, '
                . 'contains all information as json for integration run such as: '
                . 'publisherId, integrationCName, dataSourceId, params, backFill, ...'
            );
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $logger = $this->getContainer()->get('logger');
        $logger->pushHandler(new StreamHandler('php://stderr', Logger::DEBUG));
        $logger->info('Starting integration run');

        // read config file from temp dir
        // the config file is temporarily file, contains all information as json for integration run such as:
        // publisherId, integrationCName, dataSourceId, params, backFill, ...
        $integrationConfigFile = $input->getArgument('integrationConfigFile');

        if (!file_exists($integrationConfigFile)) {
            $logger->error(sprintf('Integration config file %s does not exist.', $integrationConfigFile));
            return 1;
        }

        $rawConfig = json_decode(file_get_contents($integrationConfigFile), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $logger->error(sprintf('Json decode Integration config file got errors.'));
            return 1;
        }

        // validate content
        if (!is_array($rawConfig)
            || !array_key_exists('publisherId', $rawConfig)
            || !array_key_exists('integrationCName', $rawConfig)
            || !array_key_exists('dataSourceId', $rawConfig)
            || !array_key_exists('params', $rawConfig)
            || !array_key_exists('backFill', $rawConfig)
        ) {
            $logger->error(sprintf('Integration config file contains invalid json content.'));
            return 1;
        }

        //// parse params
        $params = json_decode($rawConfig['params'], true);
        if (json_last_error() !== JSON_ERROR_NONE
            || !is_array($params)
        ) {
            $logger->error(sprintf('Integration config file contains invalid "params".'));
            return 1;
        }

        //// parse backFill
        $backFill = json_decode($rawConfig['backFill'], true);
        if (json_last_error() !== JSON_ERROR_NONE
            || !is_array($backFill)
        ) {
            $logger->error(sprintf('Integration config file contains invalid "backFill".'));
            return 1;
        }

        $config = new Config(
            $rawConfig['publisherId'],
            $rawConfig['integrationCName'],
            $rawConfig['dataSourceId'],
            $params,
            $backFill
        );

        $logger->info(
            sprintf("Config\tPublisherId: %s\tDataSourceId: %s\tIntegration cName: %s",
                $config->getPublisherId(),
                $config->getDataSourceId(),
                $config->getIntegrationCName()));

        /** @var IntegrationManagerInterface $fetcherManager */
        $fetcherManager = $this->getContainer()->get('tagcade.service.integration.integration_manager');

        /** @var IntegrationInterface $integration */
        $integration = $fetcherManager->getIntegration($config);

        /* run integration, supported retry mechanism */
        // get params
        $maxRetriesNumber = $this->getContainer()->getParameter('retry_when_fail_max_retries_number');
        if (!is_integer($maxRetriesNumber) || $maxRetriesNumber < 0) {
            $logger->error(sprintf('missing or invalid parameter retry_when_fail_max_retries_number. Expected a positive integer value.'));
            return 1;
        }

        $delayBeforeRetry = $this->getContainer()->getParameter('retry_when_fail_delay_before_retry');
        if (!is_integer($delayBeforeRetry) || $delayBeforeRetry < 0) {
            $logger->error(sprintf('missing or invalid parameter retry_when_fail_delay_before_retry. Expected a positive integer value (in seconds).'));
            return 1;
        }

        // run
        $retriedNumber = 0;
        do {
            try {
                if ($retriedNumber > 0) {
                    // delay retry
                    sleep($delayBeforeRetry);

                    $logger->info(sprintf('Integration run retry [%d].', $retriedNumber));
                }

                $integration->run($config);

                break; // break while loop if success (no exception is threw)
            } catch (LoginFailException $loginFailException) {
                $retriedNumber++;

                $logger->error(sprintf('Integration run got LoginFailException: %s.', $loginFailException->getMessage()));
            } catch (RuntimeException $runtimeException) {
                $retriedNumber++;

                $logger->error(sprintf('Integration run got RuntimeException: %s.', $runtimeException->getMessage()));
            } catch (Exception $ex) {
                // do not retry for other exceptions because the exception may come from wrong config, data, filePath, ...
                // so the retry is invalid
                $logger->error(sprintf('Integration run got Exception: %s. Skip to next integration job.', $ex->getMessage()));

                break; // break while loop if other errors
            }
        } while ($retriedNumber <= $maxRetriesNumber);

        if ($retriedNumber > 0 && $retriedNumber > $maxRetriesNumber) {
            $logger->info(sprintf('Integration run got max retries number: %d. Skip to next integration job.', $maxRetriesNumber));
        }

        return 0;
    }
}