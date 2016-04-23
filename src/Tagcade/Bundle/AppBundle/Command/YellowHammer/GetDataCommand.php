<?php

namespace Tagcade\Bundle\AppBundle\Command\YellowHammer;

use Symfony\Component\Console\Input\InputOption;
use Tagcade\DataSource\PartnerFetcherInterface;
use Tagcade\Bundle\AppBundle\Command\GetDataCommand as BaseGetDataCommand;

class GetDataCommand extends BaseGetDataCommand
{

    protected function configure()
    {
        $this
            ->setName('tc:yellow-hammer:get-data')
            ->addOption(
                'config-file',
                'c',
                InputOption::VALUE_OPTIONAL,
                'Path to the config file. See ./config/yellow.hammer.yml.dist for an example',
                './config/yellow.hammer.yml'
            )
        ;

        parent::configure();
    }

    /**
     * @return PartnerFetcherInterface
     */
    protected function getFetcher()
    {
        return $this->getContainer()->get('tagcade.data_source.fetcher.yellow_hammer');
    }


}