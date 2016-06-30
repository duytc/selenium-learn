<?php

namespace Tagcade\DataSource\Media;

use Facebook\WebDriver\Remote\RemoteWebDriver;
use Tagcade\DataSource\Media\Page\HomePage;
use Tagcade\DataSource\Media\Page\ReportingPage;
use Tagcade\DataSource\PartnerFetcherAbstract;
use Tagcade\DataSource\PartnerParamInterface;

class MediaFetcher extends PartnerFetcherAbstract implements MediaFetcherInterface
{
    public function getAllData(PartnerParamInterface $params, RemoteWebDriver $driver)
    {
        // Step 1: login
        $this->logger->info('Enter login page');
        $homePage = new HomePage($driver, $this->logger);
        $this->logger->debug('Start logging in');

        $homePage->doLogin($params->getUsername(), $params->getPassword());

        $this->logger->debug('Finish logging in');
        usleep(300);

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