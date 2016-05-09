<?php

namespace Tagcade\Bundle\AppBundle\Command\YellowHammer;

use Symfony\Component\Console\Input\InputOption;
use Tagcade\DataSource\PartnerFetcherInterface;
use Tagcade\Bundle\AppBundle\Command\GetDataCommand as BaseGetDataCommand;

class GetDataCommand extends BaseGetDataCommand
{

    const DEFAULT_CANONICAL_NAME = 'yellow-hammer';

    protected function configure()
    {
        $this
            ->setName(self::COMMAND_GET_DATA_YELLOW_HAMMER)
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