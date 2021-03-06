<?php

namespace Tagcade\DataSource\DefyMedia;

use Facebook\WebDriver\Remote\RemoteWebDriver;
use Tagcade\DataSource\DefyMedia\Page\HomePage;
use Tagcade\DataSource\DefyMedia\Page\ReportingPage;
use Tagcade\DataSource\PartnerFetcherAbstract;
use Tagcade\DataSource\PartnerParamInterface;

class DefyMediaFetcher extends PartnerFetcherAbstract implements DefyMediaFetcherInterface
{
    public function getAllData(PartnerParamInterface $params, RemoteWebDriver $driver)
    {
        // Step 1: login
        $this->logger->info('enter login page');
        $homePage = new HomePage($driver, $this->logger);
        $isLogin = $homePage->doLogin($params->getUsername(), $params->getPassword());

        if(false == $isLogin) {
            $this->logger->warning('Login system failed!');
            return;
        }

        $this->logger->debug('finish logging in');
        usleep(300);

        $this->logger->debug('enter download report page');
        $reportingPage = new ReportingPage($driver, $this->logger);
        $reportingPage->setDownloadFileHelper($this->downloadFileHelper);
        $reportingPage->setConfig($params->getConfig());

        if (!$reportingPage->isCurrentUrl()) {
            $reportingPage->navigate();
        }

        $this->logger->info('start downloading reports');
        $reportingPage->getAllTagReports($params->getStartDate(), $params->getEndDate());
        $this->logger->info('finish downloading reports');
    }
}