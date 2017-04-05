<?php

namespace Tagcade\Service\Fetcher\Fetchers\Media;

use Facebook\WebDriver\Remote\RemoteWebDriver;
use Tagcade\Service\Fetcher\Fetchers\Media\Page\HomePage;
use Tagcade\Service\Fetcher\Fetchers\Media\Page\ReportingPage;
use Tagcade\Service\Fetcher\PartnerFetcherAbstract;
use Tagcade\Service\Fetcher\Params\PartnerParamInterface;

class MediaNetFetcher extends PartnerFetcherAbstract implements MediaNetFetcherInterface
{
    /**
     * @param PartnerParamInterface $params
     * @param RemoteWebDriver $driver
     */
    public function getAllData(PartnerParamInterface $params, RemoteWebDriver $driver)
    {
        // Step 1: login
        $this->logger->info('Enter login page');
        $homePage = new HomePage($driver, $this->logger);
        $this->logger->debug('Start logging in');

        $homePage->doLogin($params->getUsername(), $params->getPassword());

        $this->logger->debug('Finish logging in');
        $this->logger->info('Enter download report page');
        $reportingPage = new ReportingPage($driver, $this->logger);
        $reportingPage->setDownloadFileHelper($this->getDownloadFileHelper());
        $reportingPage->setConfig($params->getConfig());

        if (!$reportingPage->isCurrentUrl()) {
            $reportingPage->navigate();
        }

        $this->logger->info('Start downloading reports');
        $reportingPage->getAllTagReports($params->getStartDate(), $params->getEndDate());
        $this->logger->info('Finish downloading reports');
    }
}