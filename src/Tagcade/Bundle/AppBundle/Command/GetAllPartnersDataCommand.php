<?php

namespace Tagcade\Bundle\AppBundle\Command;

use Crypto;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;


use Symfony\Bundle\FrameworkBundle\Command\Command;

use Symfony\Component\Console\Input\ArrayInput;

class GetAllPartnersDataCommand extends ContainerAwareCommand
{
    protected $supportedPartners = [];

    function __construct(array $partners)
    {
        parent::__construct();

       $this->supportedPartners = $partners;
    }

    protected function configure()
    {
        $this
            ->setName('tc:unified-report-fetcher:get-data')
            ->addOption(
                'publisher',
                'p',
                InputOption::VALUE_OPTIONAL,
                'fetcher for a publisher'
            )
            ->addOption(
                'partner-cname',
                'pa',
                InputOption::VALUE_OPTIONAL,
                'fetcher for partner cname'
            )
            ->addOption(
                'start-date',
                'f',
                InputOption::VALUE_OPTIONAL,
                'Start date (YYYY-MM-DD) to get report.',
                (new \DateTime('yesterday'))->format('Y-m-d')
            )
            ->addOption(
                'end-date',
                't',
                InputOption::VALUE_OPTIONAL,
                'End date (YYYY-MM-DD) to get report.'
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
                'quit-web-driver-after-run',
                null,
                InputOption::VALUE_NONE,
                'If set, webdriver will quit after each run. The session will be clear as well.'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container = $this->getContainer();
        $logger = $container->get('logger');

        $publisher = $input->getOption('publisher');
        $partnerCName = $input->getOption('partner-cname');
        $startDate = $input->getOption('start-date');
        $endDate = $input->getOption('end-date');
        $dataPath= $input->getOption('data-path');
        $newSession = $input->getOption('force-new-session');
        $quitWebDriver = $input->getOption('quit-web-driver-after-run');


        //Validate input command

        if(null != $startDate && !preg_match('/\d{4}\-\d{2}-\d{2}/',$startDate)) {
            $logger->error("Invalid start date format. Expect date format (YYYY-MM-DD)");
            return;
        }

        if(null != $endDate && !preg_match('/\d{4}\-\d{2}-\d{2}/',$startDate)) {
            $logger->error("Invalid end date format. Expect date format (YYYY-MM-DD)");
            return;
        }

        if ($partnerCName != null && !array_key_exists($partnerCName,  $this->supportedPartners)) {
            $logger->info(sprintf("Not supported that partner cname: %s", $partnerCName));
            return;
        }

        foreach ($this->supportedPartners as $partner => $command) {
            if(!!$partnerCName && $partner != $partnerCName) {
                continue;
            }

            $logger->info(sprintf('Start run command %s for partner %s', $command, $partner));

            try {
                $runCommand = $this->getApplication()->find($command);

                if (!$runCommand instanceof GetDataCommand) {
                    $logger->error(sprintf('Not found command %s', $command));
                    continue;
                }

                $arguments = array(
                    '--publisher' => $publisher,
                    '--partner-cname' => $partner,
                    '--start-date' => $startDate,
                    '--end-date' => $endDate,
                    '--data-path' => $dataPath,
                    '--force-new-session' => $newSession,
                    '--quit-web-driver-after-run' => $quitWebDriver
                );
                $input = new ArrayInput($arguments);
                $result = $runCommand->run($input,$output);
                $logger->info(sprintf('Run command %s finished with exit code %s', $command, $result));
            }
            catch(\Exception $e) {
                $message = $e->getMessage() ? $e->getMessage() : $e->getTraceAsString();
                $logger->critical($message);
            }
        }
    }
}