<?php


namespace Tagcade\Bundle\AppBundle\Command\Ads4Games;
use Tagcade\Bundle\AppBundle\Command\GetDataCommand as BaseGetDataCommand;
use Tagcade\DataSource\PartnerFetcherInterface;


class GetDataCommand extends BaseGetDataCommand  {

    const DEFAULT_CANONICAL_NAME = 'ads4games';

    protected function configure()
    {
        $this
            ->setName('tc:ads4games:get-data')
        ;

        parent::configure();
    }

    /**
     * @return PartnerFetcherInterface
     */
    protected function getFetcher()
    {
        return $this->getContainer()->get('tagcade.data_source.fetcher.ads4games');
    }

} 