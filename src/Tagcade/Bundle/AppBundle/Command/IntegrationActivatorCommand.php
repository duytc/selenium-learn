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
            ->setName('tc:unified-report-fetcher:activator:run');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /* create logger */
        $this->createLogger();

        $this->logger->info('Start running integration activator');

        /* run activator service */
        /** @var IntegrationActivatorInterface $activatorService */
        $activatorService = $this->getContainer()->get('tagcade.service.integration_activator');

        $result = $activatorService->createExecutionJobs();

        if (!$result) {
            $this->logger->error('Complete running integration activator with error');
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