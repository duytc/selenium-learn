<?php

namespace Tagcade\Bundle\AppBundle\Command\PulsePoint;

use Facebook\WebDriver\Remote\RemoteWebDriver;
use Symfony\Component\Console\Input\InputOption;
use Tagcade\DataSource\PartnerFetcherInterface;
use Tagcade\Bundle\AppBundle\Command\GetDataCommand as BaseGetDataCommand;
use Tagcade\DataSource\PartnerParamInterface;

class GetAccountDataCommand extends BaseGetDataCommand
{

    const DEFAULT_CANONICAL_NAME = 'pulse-point';

    protected function configure()
    {
        $this
            ->setName(self::COMMAND_GET_DATA_PULSE_POINT)
        ;

        parent::configure();
    }

    protected function handleGetDataByDateRange(PartnerParamInterface $params, RemoteWebDriver $driver)
    {
        $startDate = $params->getStartDate();
        $endDate = $params->getEndDate();
        $end = $endDate->modify('+1 day');
        $interval = new \DateInterval('P1D');
        $dateRange = new \DatePeriod($startDate, $interval ,$end);


        foreach ($dateRange as $date) {
            /**
             * @var \DateTime $date
             */
            $params->setStartDate($date);
            $params->setEndDate(clone $date);

            $this->logger->info(sprintf('Fetching report with start-date=%s, end-date=%s', $date->format('Y-m-d'), $date->format('Y-m-d')));
            $this->fetcher->getAllData($params, $driver);
            $this->logger->info(sprintf('Finished fetching report with start-date=%s, end-date=%s', $date->format('Y-m-d'), $date->format('Y-m-d')));

            usleep(300);
        }
    }

    /**
     * @return PartnerFetcherInterface
     */
    protected function getFetcher()
    {
        return $this->getContainer()->get('tagcade.data_source.fetcher.pulse_point');
    }


}