<?php


namespace Tagcade\DataSource\NativeAds;


use Facebook\WebDriver\Remote\RemoteWebDriver;
use Tagcade\DataSource\NativeAds\Page\HomePage;
use Tagcade\DataSource\NativeAds\Page\ReportingPage;
use Tagcade\DataSource\PartnerFetcherAbstract;
use Tagcade\DataSource\PartnerParamInterface;


class NativeAdsFetcher extends PartnerFetcherAbstract implements  NativeAdsFetcherInterface {

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

        $result = $homePage->doLogin($params->getUsername(), $params->getPassword());
        if (false == $result) {
            $this->logger->info('Can not login this system');
            return;
        }

        usleep(500);

        $this->logger->debug('Enter download report page');
        $reportingPage = new ReportingPage($driver, $this->logger);
        $reportingPage->setDownloadFileHelper($this->getDownloadFileHelper());
        $reportingPage->setConfig($params->getConfig());

        $this->logger->debug('set all configs');
        if (!$reportingPage->isCurrentUrl()) {
            $this->logger->debug('Comming to navigate');
            $reportingPage->navigate();
        }

        $this->logger->info('Start downloading reports');
        $reportingPage->getAllTagReports($params->getStartDate(), $params->getEndDate());
        $this->logger->info('Finish downloading reports');
    }
} 