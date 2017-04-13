<?php

namespace Tagcade\Service\Fetcher\Fetchers\Sovrn;

use Facebook\WebDriver\Remote\RemoteWebDriver;
use Psr\Log\LoggerInterface;
use Tagcade\Service\Fetcher\Fetchers\Sovrn\Page\EarningPage;
use Tagcade\Service\Fetcher\Fetchers\Sovrn\Page\HomePage;
use Tagcade\Service\Fetcher\Params\PartnerParamInterface;
use Tagcade\Service\Fetcher\PartnerFetcherAbstract;

class SovrnFetcher extends PartnerFetcherAbstract implements SovrnFetcherInterface
{
    /**
     * @param PartnerParamInterface $params
     * @param RemoteWebDriver $driver
     */
    public function getAllData(PartnerParamInterface $params, RemoteWebDriver $driver)
    {
        $this->logger->debug('entering download report page');
        $earningPage = new EarningPage($driver, $this->logger);
        $earningPage->setDownloadFileHelper($this->downloadFileHelper);
        $earningPage->setConfig($params->getConfig());

        if (!$earningPage->isCurrentUrl()) {
            $earningPage->navigate();
        }

        $this->logger->info('start downloading reports');
        $earningPage->getAllTagReports($params->getStartDate(), $params->getEndDate());
        $this->logger->info('finish downloading reports');
    }

    /**
     * @inheritdoc
     */
    function getHomePage(RemoteWebDriver $driver, LoggerInterface $logger)
    {
        return new HomePage($driver, $this->logger);
    }
}