<?php


namespace Tagcade\Service\Fetcher\Fetchers\NativeAds;


use Facebook\WebDriver\Remote\RemoteWebDriver;
use Tagcade\Service\Fetcher\Fetchers\NativeAds\Page\HomePage;
use Tagcade\Service\Fetcher\Fetchers\NativeAds\Page\ReportingPage;
use Tagcade\Service\Fetcher\PartnerFetcherAbstract;
use Tagcade\Service\Fetcher\PartnerParamInterface;


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

        $isLogin = $homePage->doLogin($params->getUsername(), $params->getPassword());

        if (false == $isLogin) {
            $this->logger->warning('Login system failed!');
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