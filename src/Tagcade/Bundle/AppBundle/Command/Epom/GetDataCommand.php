<?php


namespace Tagcade\Bundle\AppBundle\Command\Epom;
use Tagcade\Bundle\AppBundle\Command\GetDataCommand as BaseGetDataCommand;
use Tagcade\DataSource\PartnerFetcherInterface;


class GetDataCommand extends BaseGetDataCommand  {

    const DEFAULT_CANONICAL_NAME = 'epom-market';

    protected function configure()
    {
        $this
            ->setName('tc:epom-market:get-data')
        ;

        parent::configure();
    }

    /**
     * @return PartnerFetcherInterface
     */
    protected function getFetcher()
    {
        return $this->getContainer()->get('tagcade.data_source.fetcher.epom_market');
    }
}