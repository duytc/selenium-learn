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
            ->setName(self::COMMAND_GET_DATA_SOVRN)
        ;

        parent::configure();
    }

    protected function handleGetDataByDateRange(PartnerParamInterface $params, RemoteWebDriver $driver)
    {
        $startDate = $params->getStartDate();
        $endDate = $params->getEndDate();

        $newEndDate = clone $startDate;
        $processedStartDate = false;
        do {
            $myStartDate = clone $newEndDate;
            if ($processedStartDate == true) {
                $myStartDate->add(new \DateInterval('P1D')); // avoid overlapping with previous end date data that has been downloaded.
            }
            $newEndDate->add(new \DateInterval('P30D'));
            if ($newEndDate > $endDate) {
                $newEndDate = $endDate;
            }

            $params->setStartDate($myStartDate);
            $params->setEndDate($newEndDate);

            $this->logger->info(sprintf('Fetching report with start-date=%s, end-date=%s', $myStartDate->format('Y-m-d'), $newEndDate->format('Y-m-d')));

            $this->fetcher->getAllData($params, $driver);

            $this->logger->info(sprintf('Finished fetching report with start-date=%s, end-date=%s', $myStartDate->format('Y-m-d'), $newEndDate->format('Y-m-d')));


            $processedStartDate = true;
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