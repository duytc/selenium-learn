<?php

namespace Tagcade\Service\Fetcher\Fetchers\Ads4Games;

use Facebook\WebDriver\Remote\RemoteWebDriver;
use Psr\Log\LoggerInterface;
use Tagcade\Service\Fetcher\Fetchers\Ads4Games\Page\HomePage;
use Tagcade\Service\Fetcher\Fetchers\Ads4Games\Page\Reportingpage;
use Tagcade\Service\Fetcher\Params\PartnerParamInterface;
use Tagcade\Service\Fetcher\PartnerFetcherAbstract;

class Ads4GamesFetcher extends PartnerFetcherAbstract implements Ads4GamesFetcherInterface
{
    /**
     * @param PartnerParamInterface $params
     * @param RemoteWebDriver $driver
     */
    public function getAllData(PartnerParamInterface $params, RemoteWebDriver $driver)
    {
        // usleep(300);

        $this->logger->debug('Enter download report page');
        $reportingPage = new Reportingpage($driver, $this->logger);
        $reportingPage->setDownloadFileHelper($this->getDownloadFileHelper());
        $reportingPage->setConfig($params->getConfig());

        if (!$reportingPage->isCurrentUrl()) {
            $reportingPage->navigate();
        }

        $this->logger->info('Start downloading reports');
        $reportingPage->getAllTagReports($params->getStartDate(), $params->getEndDate());
        $this->logger->info('Finish downloading reports');
    }

    /**
     * @inheritdoc
     */
    public function getHomePage(RemoteWebDriver $driver, LoggerInterface $logger)
    {
        return $homePage = new HomePage($driver, $this->logger);
    }
}