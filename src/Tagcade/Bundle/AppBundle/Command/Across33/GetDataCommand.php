<?php

namespace Tagcade\Bundle\AppBundle\Command\Across33;

use Symfony\Component\Console\Input\InputOption;
use Tagcade\DataSource\PartnerFetcherInterface;
use Tagcade\Bundle\AppBundle\Command\GetDataCommand as BaseGetDataCommand;

class GetDataCommand extends BaseGetDataCommand
{

    const DEFAULT_CANONICAL_NAME = '33across';

    protected function configure()
    {
        $this
            ->setName('tc:across33:get-data')
        ;

        parent::configure();
    }

    /**
     * @return PartnerFetcherInterface
     */
    protected function getFetcher()
    {
        return $this->getContainer()->get('tagcade.data_source.fetcher.across33');
    }
}