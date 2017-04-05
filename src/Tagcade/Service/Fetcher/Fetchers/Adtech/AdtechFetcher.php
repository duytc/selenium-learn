<?php


namespace Tagcade\Service\Fetcher\Fetchers\Adtech;


use Facebook\WebDriver\Remote\RemoteWebDriver;
use Tagcade\Service\Fetcher\Fetchers\Adtech\Page\HomePage;
use Tagcade\Service\Fetcher\Fetchers\Adtech\Page\ReportingPage;
use Tagcade\Service\Fetcher\PartnerFetcherAbstract;
use Tagcade\Service\Fetcher\Params\PartnerParamInterface;

class AdtechFetcher extends PartnerFetcherAbstract implements AdtechFetcherInterface
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
        $isLogin = $homePage->doLogin($params->getUsername(), $params->getPassword());

        if (false == $isLogin) {
            $this->logger->warning('Login system failed');
            return;
        }

        $this->logger->info('Finish logging in');
        sleep(5);

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