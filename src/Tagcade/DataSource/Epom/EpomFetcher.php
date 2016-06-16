<?php


namespace Tagcade\DataSource\Epom;


use Facebook\WebDriver\Remote\RemoteWebDriver;
use Tagcade\DataSource\Epom\Page\HomePage;
use Tagcade\DataSource\Epom\Page\Reportingpage;
use Tagcade\DataSource\PartnerFetcherAbstract;
use Tagcade\DataSource\PartnerParamInterface;

class EpomFetcher extends PartnerFetcherAbstract implements EpomFetcherInterface {

    public function getAllData(PartnerParamInterface $params, RemoteWebDriver $driver)
    {
        // Step 1: login
        $this->logger->info('Enter login page');
        $homePage = new HomePage($driver, $this->logger);
        $this->logger->info('Start logging in');

        $result = $homePage->doLogin($params->getUsername(), $params->getPassword());
        if (false == $result) {
            $this->logger->info('Can not login this system');
            return;
        }
        $this->logger->info('Finish logging in');
        usleep(300);

        $this->logger->info('Enter download report page');
        $reportingPage = new ReportingPage($driver, $this->logger);
        $reportingPage->setDownloadFileHelper($this->getDownloadFileHelper());

        if (!$reportingPage->isCurrentUrl()) {
            $reportingPage->navigate();
        }

        $this->logger->info('Start downloading reports');
        $reportingPage->getAllTagReports($params->getStartDate(), $params->getEndDate());
        $this->logger->info('Finish downloading reports');
    }
}