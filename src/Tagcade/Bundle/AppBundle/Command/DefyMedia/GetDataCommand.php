<?php

namespace Tagcade\Bundle\AppBundle\Command\DefyMedia;

use Symfony\Component\Console\Input\InputOption;
use Tagcade\DataSource\PartnerFetcherInterface;
use Tagcade\Bundle\AppBundle\Command\GetDataCommand as BaseGetDataCommand;

class GetDataCommand extends BaseGetDataCommand
{

    const DEFAULT_CANONICAL_NAME = 'defy-media';

    protected function configure()
    {
        $this
            ->setName(self::COMMAND_GET_DATA_DEFY_MEDIA)
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