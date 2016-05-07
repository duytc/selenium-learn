<?php

namespace Tagcade\Bundle\AppBundle\Command\Across33;

use Facebook\WebDriver\Remote\RemoteWebDriver;
use Symfony\Component\Console\Input\InputOption;
use Tagcade\DataSource\PartnerFetcherInterface;
use Tagcade\Bundle\AppBundle\Command\GetDataCommand as BaseGetDataCommand;
use Tagcade\DataSource\PartnerParamInterface;

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

    protected function handleGetDataByDateRange(PartnerParamInterface $params, RemoteWebDriver $driver)
    {
        $this->logger->info('We support getting Month to date report for Across33 only. Your date range would not be affected.');

        parent::handleGetDataByDateRange($params, $driver);
    }

    /**
     * @return PartnerFetcherInterface
     */
    protected function getFetcher()
    {
        return $this->getContainer()->get('tagcade.data_source.fetcher.across33');
    }
}