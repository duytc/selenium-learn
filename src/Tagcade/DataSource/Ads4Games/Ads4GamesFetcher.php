<?php


namespace Tagcade\DataSource\Ads4Games;


use Facebook\WebDriver\Remote\RemoteWebDriver;
use Tagcade\DataSource\Ads4Games\Page\HomePage;
use Tagcade\DataSource\Ads4Games\Page\Reportingpage;
use Tagcade\DataSource\PartnerFetcherAbstract;
use Tagcade\DataSource\PartnerParamInterface;

class Ads4GamesFetcher extends PartnerFetcherAbstract implements Ads4GamesFetcherInterface {

    public function getAllData(PartnerParamInterface $params, RemoteWebDriver $driver)
    {
        // Step 1: login
        $this->logger->info('Enter login page');
        $homePage = new HomePage($driver, $this->logger);
        $homePage->doLogin($params->getUsername(), $params->getPassword());
        $this->logger->info('Finish logging in');

        usleep(300);

        $this->logger->debug('Enter download report page');
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