<?php

namespace Tagcade\Bundle\AppBundle\Command;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\LockHandler;
use Tagcade\Service\Integration\IntegrationActivatorInterface;

class IntegrationActivatorByDataSourceCommand extends ContainerAwareCommand
{
    /** @var LoggerInterface */
    protected $logger;

    protected function configure()
    {
        $this
            ->setName('tc:unified-report-fetcher:activator:run:datasource')
            ->addArgument('dataSourceId', InputOption::VALUE_REQUIRED, 'Integration name')
            ->addOption('parameters', 'p', InputOption::VALUE_OPTIONAL,
                'Integration parameters (optional) as name:type, allow multiple parameters separated by comma. 
                Supported types are: plainText (default), date (Y-m-d), dynamicDateRange (last 1,2,3... days) 
                and secure (will be encrypted in database and not show value in ui). 
                e.g. -p "username,password:secure,startDate:date"');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /* get inputs */
        $dataSourceId = $input->getArgument('dataSourceId');
        $paramsString = $input->getOption('parameters');
        $params = $this->parseParams($paramsString);

        /* create logger */
        $this->createLogger();

        $this->logger->info('Start running integration activator');

        // create lock and if other process is running
        // this make sure only one integration activator process is running at a time
        $lock = new LockHandler($this->getName());

        if (!$lock->lock()) {
            $this->logger->info(sprintf('%s: The command is already running in another process.', $this->getName()));
            return;
        }

        /* run activator service */
        /** @var IntegrationActivatorInterface $activatorService */
        $activatorService = $this->getContainer()->get('tagcade.service.integration_activator');

        $result = $activatorService->createExecutionJobForDataSource($dataSourceId, $params);

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

    /**
     * parse Params. Support , as separator between params and : as separator between name and type
     *
     * @param string $paramsString
     * @return array|null return null if paramsString empty|null. Return array if valid, array format as:
     * [
     * [ 'key' => <param name>, 'type' => <param type> ],
     * ...
     * ]
     */
    private function parseParams($paramsString)
    {
        if (empty($paramsString)) {
            return null;
        }

        $params = explode(',', $paramsString);

        $params = array_map(function ($param) {
            // parse name:type
            $paramNameAndType = explode(':', trim($param));

            return [
                'key' => $paramNameAndType[0],
                'type' => 'plainText',
                'value' => $paramNameAndType[1]
            ];
        }, $params);

        return $params;
    }
}
