<?php

namespace Tagcade\Bundle\AppBundle\Command\Ads4Games;

use Tagcade\Bundle\AppBundle\Command\GetDataCommand as BaseGetDataCommand;
use Tagcade\DataSource\PartnerFetcherInterface;
use Symfony\Component\Console\Input\InputInterface;
use Tagcade\DataSource\PartnerParamInterface;

class GetDataCommand extends BaseGetDataCommand  {

    const DEFAULT_CANONICAL_NAME = 'ads4games';

    protected function configure()
    {
        $this
            ->setName('tc:ads4games:get-data')
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
        return $this->getContainer()->get('tagcade.data_source.fetcher.ads4games');
    }

} 