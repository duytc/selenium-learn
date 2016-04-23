<?php

namespace Tagcade\Bundle\AppBundle\Command\Sovrn;

use Symfony\Component\Console\Input\InputOption;
use Tagcade\DataSource\PartnerFetcherInterface;
use Tagcade\Bundle\AppBundle\Command\GetDataCommand as BaseGetDataCommand;

class GetDataCommand extends BaseGetDataCommand
{

    protected function configure()
    {
        $this
            ->setName('tc:sovrn:get-data')
            ->addOption(
                'config-file',
                'c',
                InputOption::VALUE_OPTIONAL,
                'Path to the config file. See ./config/sovrn.yml.dist for an example',
                './config/sovrn.yml'
            )
        ;

        parent::configure();
    }

    /**
     * @return PartnerFetcherInterface
     */
    protected function getFetcher()
    {
        return $this->getContainer()->get('tagcade.data_source.fetcher.sovrn');
    }
}