<?php

namespace Tagcade\Bundle\AppBundle\Command\AdMeta;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class GetDataCommand extends ContainerAwareCommand {
    protected function configure()
    {
        $this
            ->setName('tc:admeta:get-data')
            ->addOption(
                'config-file',
                'c',
                InputOption::VALUE_REQUIRED,
                'Path to the config file. See ./config/pulsepoint.yml.dist for an example'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('command placeholder');
    }
}