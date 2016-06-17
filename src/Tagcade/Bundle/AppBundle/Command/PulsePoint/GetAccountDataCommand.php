<?php

namespace Tagcade\Bundle\AppBundle\Command\PulsePoint;

use Facebook\WebDriver\Remote\RemoteWebDriver;
use Symfony\Component\Console\Input\InputInterface;
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
            ->setName('tc:pulse-point:get-data')
        ;

        parent::configure();
    }


    protected function getDataForPublisher(InputInterface $input, $publisherId, PartnerParamInterface $params, array $config, $dataPath)
    {

        $startDate = $params->getStartDate();
        $endDate = $params->getEndDate();
        $end = $endDate->modify('+1 day');
        $interval = new \DateInterval('P1D');
        $dateRange = new \DatePeriod($startDate, $interval ,$end);

        foreach ($dateRange as $date) {
            $params->setStartDate($date);
            $params->setEndDate($date);

            parent::getDataForPublisher($input, $publisherId, $params, $config, $dataPath);
        }

        return 0;
    }

    /**
     * @return PartnerFetcherInterface
     */
    protected function getFetcher()
    {
        return $this->getContainer()->get('tagcade.data_source.fetcher.pulse_point');
    }


}