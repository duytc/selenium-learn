<?php

namespace Tagcade\Bundle\AppBundle\Command;

use Crypto;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;
use Tagcade\DataSource\PartnerFetcherInterface;
use Tagcade\DataSource\PartnerParamInterface;
use Tagcade\DataSource\PartnerParams;
use Tagcade\Service\Core\TagcadeRestClientInterface;

class GetAllPartnersDataCommand extends ContainerAwareCommand
{
    static $SUPPORTED_PARTNERS = [
        '33Across'      => 'tc:across33:get-data',
        'defy-media'    => 'tc:defy-media:get-data',
        'komoona'       => 'tc:komoona:get-data',
        'pulse-point'   => 'tc:pulse-point:get-data',
        'sovrn'         => 'tc:sovrn:get-data',
        'yellow-hammer' => 'tc:yellow-hammer:get-data'
    ];

    protected function configure()
    {
        $this
            ->setName('tc:unified-report-fetcher:get-data')
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

        //TODO getting reports for all partners
        // Step 1. Get all partner cnames

        // Step 2. Validate in supported list. Print log if not

        // Step 3. Get reports for each partner by invoking partner command
    }
}