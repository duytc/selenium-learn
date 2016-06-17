<?php

namespace Tagcade\DataSource\Komoona;


use Facebook\WebDriver\Remote\RemoteWebDriver;
use Tagcade\DataSource\Komoona\Page\HomePage;
use Tagcade\DataSource\Komoona\Page\IncomeReportPage;
use Tagcade\DataSource\PartnerFetcherAbstract;
use Tagcade\DataSource\PartnerParamInterface;

class KomoonaFetcher extends PartnerFetcherAbstract implements KomoonaFetcherInterface
{
    public function getAllData(PartnerParamInterface $params, RemoteWebDriver $driver)
    {
        // Step 1: login
        $homePage = new HomePage($driver, $this->logger);
        $this->logger->info(sprintf('Trying to login to home page %s', $homePage->getPageUrl()));
        $homePage->doLogin($params->getUsername(), $params->getPassword());
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