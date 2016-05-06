<?php

namespace Tagcade\Bundle\AppBundle\Command\Sovrn;

use Facebook\WebDriver\Remote\RemoteWebDriver;
use Symfony\Component\Console\Input\InputOption;
use Tagcade\DataSource\PartnerFetcherInterface;
use Tagcade\Bundle\AppBundle\Command\GetDataCommand as BaseGetDataCommand;
use Tagcade\DataSource\PartnerParamInterface;

class GetDataCommand extends BaseGetDataCommand
{

    const DEFAULT_CANONICAL_NAME = 'sovrn';

    protected function configure()
    {
        $this
            ->setName('tc:sovrn:get-data')
        ;

        parent::configure();
    }

    protected function handleGetDataByDateRange(PartnerParamInterface $params, RemoteWebDriver $driver)
    {
        $startDate = $params->getStartDate();
        $endDate = $params->getEndDate();
        $myStartDate = clone $startDate;
        do {

            $newEndDate = $myStartDate->add(new \DateInterval('P30D'));
            if ($newEndDate > $endDate) {
                $newEndDate = $endDate;
            }

            $params->setEndDate($newEndDate);
            $this->fetcher->getAllData($params, $driver);

        }
        while($newEndDate < $endDate);
    }


    /**
     * @return PartnerFetcherInterface
     */
    protected function getFetcher()
    {
        return $this->getContainer()->get('tagcade.data_source.fetcher.sovrn');
    }
}