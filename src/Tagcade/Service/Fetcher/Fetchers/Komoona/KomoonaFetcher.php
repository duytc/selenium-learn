<?php

namespace Tagcade\Service\Fetcher\Fetchers\Komoona;


use Facebook\WebDriver\Remote\RemoteWebDriver;
use Tagcade\Service\Fetcher\Fetchers\Komoona\Page\HomePage;
use Tagcade\Service\Fetcher\Fetchers\Komoona\Page\IncomeReportPage;
use Tagcade\Service\Fetcher\PartnerFetcherAbstract;
use Tagcade\Service\Fetcher\PartnerParamInterface;

class KomoonaFetcher extends PartnerFetcherAbstract implements KomoonaFetcherInterface
{
    /**
     * @param PartnerParamInterface $params
     * @param RemoteWebDriver $driver
     */
    public function getAllData(PartnerParamInterface $params, RemoteWebDriver $driver)
    {
        // Step 1: login
        $homePage = new HomePage($driver, $this->logger);
        $this->logger->info(sprintf('Trying to login to home page %s', $homePage->getPageUrl()));
        $isLogin = $homePage->doLogin($params->getUsername(), $params->getPassword());

        if (false == $isLogin) {
            $this->logger->warning('Login system failed!');
            return;
        }

        $this->logger->debug(sprintf('The page is logged in %s', $homePage->getPageUrl()));

        // Step 2: view report
        $incomeReportPage = new IncomeReportPage($driver, $this->logger);
        $incomeReportPage->setDownloadFileHelper($this->downloadFileHelper);
        $incomeReportPage->setConfig($params->getConfig());

        $this->logger->debug(sprintf('Navigating to report page %s', $incomeReportPage->getPageUrl()));
        $incomeReportPage->navigate();

        $this->logger->info(sprintf('Getting report for page %s', $incomeReportPage->getPageUrl()));
        $incomeReportPage->getAllTagReports($params->getStartDate(), $params->getEndDate());
    }
}