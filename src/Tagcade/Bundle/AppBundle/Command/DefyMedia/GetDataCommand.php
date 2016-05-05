<?php

namespace Tagcade\Bundle\AppBundle\Command\DefyMedia;

use Symfony\Component\Console\Input\InputOption;
use Tagcade\DataSource\PartnerFetcherInterface;
use Tagcade\Bundle\AppBundle\Command\GetDataCommand as BaseGetDataCommand;

class GetDataCommand extends BaseGetDataCommand
{

    const DEFAULT_CANONICAL_NAME = 'defymedia';

    protected function configure()
    {
        $this
            ->setName('tc:defy-media:get-data')
        ;

        parent::configure();
    }

    /**
     * @return PartnerFetcherInterface
     */
    protected function getFetcher()
    {
        return $this->getContainer()->get('tagcade.data_source.fetcher.defy_media');
    }
}