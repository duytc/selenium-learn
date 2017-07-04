<?php

namespace Tagcade\Bundle\AppBundle\Command;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Tagcade\Service\Integration\IntegrationActivatorInterface;

class IntegrationActivatorCommand extends ContainerAwareCommand
{
    /** @var LoggerInterface */
    protected $logger;

    protected function configure()
    {
        $this
            ->setName('tc:unified-report-fetcher:activator:run')
            ->setDescription('Check the schedule for all integrations that need to be run and create jobs');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /* create logger */
        $this->createLogger();

        $this->logger->info('Start running integration activator');

        $container = $this->getContainer();

        $lockService = $container->get('tagcade.service.lock.lock_service');

        $lock = $lockService->lock('integration-activator-run');

        if ($lock === false) {
            $this->logger->info(sprintf('%s: The command is already running in another process.', $this->getName()));
            return;
        }

        try {
            /* run activator service */
            /** @var IntegrationActivatorInterface $activatorService */
            $activatorService = $container->get('tagcade.service.integration_activator');

            $activatorService->createExecutionJobs();
            $this->logger->info('Complete running integration activator with no error');
        } catch (\Exception $e) {
            $this->logger->notice($e);
        } finally {
            $lockService->unlock($lock);
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