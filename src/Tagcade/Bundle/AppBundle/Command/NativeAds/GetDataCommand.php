<?php


namespace Tagcade\Bundle\AppBundle\Command\NativeAds;

use Tagcade\Bundle\AppBundle\Command\GetDataCommand as BaseGetDataCommand;
use Tagcade\DataSource\PartnerFetcherInterface;


class GetDataCommand extends BaseGetDataCommand {

    const DEFAULT_CANONICAL_NAME = 'native-ads';

    protected function configure()
    {
        $this
            ->setName('tc:native-ads:get-data')
        ;

        parent::configure();
    }

    /**
     * * @return PartnerFetcherInterface
     */
    protected function getFetcher()
    {
        return $this->getContainer()->get('tagcade.data_source.fetcher.nativeads');
    }

}