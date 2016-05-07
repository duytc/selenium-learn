<?php

namespace Tagcade\Bundle\AppBundle\Command\PulsePoint;

use Symfony\Component\Console\Input\InputOption;
use Tagcade\DataSource\PartnerFetcherInterface;
use Tagcade\Bundle\AppBundle\Command\GetDataCommand as BaseGetDataCommand;

class GetAccountDataCommand extends BaseGetDataCommand
{

    const DEFAULT_CANONICAL_NAME = 'pulse-point';

    protected function configure()
    {
        $this
            ->setName('tc:pulse-point:get-data')
        ;

        parent::configure();
    }

    /**
     * @return PartnerFetcherInterface
     */
    protected function getFetcher()
    {
        return $this->getContainer()->get('tagcade.data_source.fetcher.pulse_point');
    }


}