<?php


namespace Tagcade\Bundle\AppBundle\Command\CpmBase;



use Tagcade\DataSource\CpmBase\CpmBaseFetcherInterface;
use Tagcade\Bundle\AppBundle\Command\GetDataCommand as BaseGetDataCommand;

class GetDataCommand extends BaseGetDataCommand {

    const DEFAULT_CANONICAL_NAME = 'cpm-base';

    protected function configure()
    {
        $this
            ->setName('tc:cpm-base:get-data')
        ;

        parent::configure();
    }

    /**
     * @return CpmBaseFetcherInterface
     */
    protected function getFetcher()
    {
        return $this->getContainer()->get('tagcade.data_source.fetcher.cpmbase');
    }

} 