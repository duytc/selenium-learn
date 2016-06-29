<?php

namespace Tagcade\Bundle\AppBundle\Command\Districtm;

use Facebook\WebDriver\Remote\RemoteWebDriver;
use Symfony\Component\Console\Input\InputOption;
use Tagcade\DataSource\PartnerFetcherInterface;
use Tagcade\Bundle\AppBundle\Command\GetDataCommand as BaseGetDataCommand;
use Tagcade\DataSource\PartnerParamInterface;

class GetDataCommand extends BaseGetDataCommand
{

    const DEFAULT_CANONICAL_NAME = 'districtm';

    protected function configure()
    {
        $this
            ->setName('tc:districtm:get-data')
        ;

        parent::configure();
    }

    /**
     * @return PartnerFetcherInterface
     */
    protected function getFetcher()
    {
        return $this->getContainer()->get('tagcade.data_source.fetcher.districtm');
    }
}