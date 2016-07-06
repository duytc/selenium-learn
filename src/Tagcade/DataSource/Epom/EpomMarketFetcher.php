<?php


namespace Tagcade\DataSource\Epom;


use Facebook\WebDriver\Remote\RemoteWebDriver;
use Tagcade\DataSource\Epom\Page\HomePage;
use Tagcade\DataSource\Epom\Page\Reportingpage;
use Tagcade\DataSource\PartnerFetcherAbstract;
use Tagcade\DataSource\PartnerParamInterface;

class EpomMarketFetcher extends PartnerFetcherAbstract implements EpomMarketFetcherInterface {

    public function getAllData(PartnerParamInterface $params, RemoteWebDriver $driver)
    {
        // Step 1: login
        $this->logger->info('Enter login page');
        $homePage = new HomePage($driver, $this->logger);
        $this->logger->info('Start logging in');

        $isLogin = $homePage->doLogin($params->getUsername(), $params->getPassword());
        if(false == $isLogin) {
            $this->logger->warning('Login system failed!');
            return;
        }
        $this->logger->info('Finish logging in');

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