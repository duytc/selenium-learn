<?php

namespace Tagcade\Bundle\AppBundle\Command;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Tagcade\Service\Integration\Config;
use Tagcade\Service\Integration\IntegrationActivatorInterface;

class IntegrationActivatorByDataSourceCommand extends ContainerAwareCommand
{
    /** @var LoggerInterface */
    protected $logger;

    protected function configure()
    {
        $this
            ->setName('tc:unified-report-fetcher:activator:datasource:run')
            ->setDescription('Schedule a specific data source integration to be run. You can override the normal schedule for the integration using this command. This is good for testing')
            ->addArgument('dataSourceIds', InputArgument::REQUIRED, 'Data source, input with id or ids as array without space. Example 2 or 15,17,3,9')
            ->addOption('custom-parameters', 'p', InputOption::VALUE_OPTIONAL,
                'Custom Integration parameters (optional) as name:type:value, allow multiple parameters separated by comma. 
                Supported types are: plainText (default), date (Y-m-d), dynamicDateRange (last 1,2,3... days) 
                , secure (will be encrypted in database and not show value in ui) and regex.  
                e.g. -p "username:plainText:admin,password:secure:MTIzNDU2Nw==,dateRange:dynamicDateRange:last 2 days,account:regex:Division D"')
            ->addOption('force', 'f', InputOption::VALUE_NONE,
                'Run update integration without checking schedule')
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
        $dataSourceIds = $input->getArgument('dataSourceIds');

        if (empty($dataSourceIds)) {
            $this->logger->warning('Missing data source id');
            return;
        }

        $customParamsString = $input->getOption('custom-parameters');
        $customParams = $this->parseParams($customParamsString);

        $isForceRun = $input->getOption('force');

        $this->logger->info('Start running integration activator');

        /* run activator service */
        /** @var IntegrationActivatorInterface $activatorService */
        $activatorService = $this->getContainer()->get('tagcade.service.integration_activator');
        $startDate = $input->getOption('startDate');
        $endDate = $input->getOption('endDate');

        if (!empty($startDate) && empty($endDate)) {
            $endDate = $startDate;
        }

        $dataSourceIds = explode(',', $dataSourceIds);
        foreach ($dataSourceIds as $dataSourceId) {
            if (!empty($startDate)) {
                $result = $activatorService->createExecutionJobForDataSource(
                    $dataSourceId,
                    $customParams,
                    $isForceRun = true,
                    $startDate,
                    $endDate
                );
            } else {
                $result = $activatorService->createExecutionJobForDataSource(
                    $dataSourceId,
                    $customParams,
                    $isForceRun
                );
            }

            if (is_array($result)) {
                $this->logger->info(sprintf('There are %d integration should not be run - DataSource %d . Details: ', $dataSourceId, count($result)));
                foreach ($result as $activatorMessage) {
                    $this->logger->warning($activatorMessage);
                }
            }

            if (!$result) {
                $this->logger->notice(sprintf('Complete running integration activator with error - DataSource %d', $dataSourceId));
            } else {
                $this->logger->info(sprintf('Complete running integration activator with no error - DataSource %d', $dataSourceId));
            }
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
     *      [ 'key' => <param name>, 'type' => <param type>, 'value' => <param value> ],
     *      ...
     * ]
     */
    private function parseParams($paramsString)
    {
        if (empty($paramsString)) {
            return null;
        }

        $params = explode(',', $paramsString);

        $params = array_map(function ($param) {
            // parse name:type:value
            $paramNameAndType = explode(':', trim($param));

            return [
                'key' => $paramNameAndType[0],
                'type' => count($paramNameAndType) < 2 ? Config::PARAM_TYPE_PLAIN_TEXT : $paramNameAndType[1],
                'value' => count($paramNameAndType) < 3 ? null : $paramNameAndType[2]
            ];
        }, $params);

        return $params;
    }
}
