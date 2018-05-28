<?php

namespace Tagcade\Bundle\AppBundle\Command;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Tagcade\Service\Integration\IntegrationActivatorInterface;

class IntegrationActivatorByIntegrationCommand extends ContainerAwareCommand
{
    /** @var LoggerInterface */
    protected $logger;

    protected function configure()
    {
        $this
            ->setName('tc:unified-report-fetcher:activator:integration:run')
            ->setDescription('create schedule integration job from a specific integrationId. 
                              This supports to run an integration for all data sources that use it for a specific date or date range.
                              Always run updating integration schedule without checking schedule
            You can override the normal schedule for the integration using this command. This is good for testing')
            ->addArgument('integrationId', InputArgument::REQUIRED, 'Integration Id')
            ->addOption('startDate', 't', InputOption::VALUE_OPTIONAL,
                'Update schedule of integration')
            ->addOption('endDate', 'y', InputOption::VALUE_OPTIONAL,
                'Update schedule of integration');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /* create logger */
        $this->createLogger();

        /* get inputs */
        $integrationId = $input->getArgument('integrationId');

        if (empty($integrationId)) {
            $this->logger->warning('Missing integration id');
            return;
        }

        $this->logger->info('Start running integration activator');

        /* run activator service */
        /** @var IntegrationActivatorInterface $activatorService */
        $activatorService = $this->getContainer()->get('tagcade.service.integration_activator');
        $startDate = $input->getOption('startDate');
        $endDate = $input->getOption('endDate');

        if (!empty($startDate) && empty($endDate)) {
            $endDate = $startDate;
        }

        $result = $activatorService->createExecutionJobForDataSourcesByIntegration(
            $integrationId,
            $isForceRun = true,
            $startDate,
            $endDate
        );

        if (is_array($result)) {
            $this->logger->info(sprintf('There are %d integration should not be run. Details: ', count($result)));
            foreach ($result as $activatorMessage) {
                $this->logger->warning($activatorMessage);
            }
        }

        if (!$result) {
            $this->logger->notice('Complete running integration activator with error');
        } else {
            $this->logger->info('Complete running integration activator with no error');
        }
    }

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
}
