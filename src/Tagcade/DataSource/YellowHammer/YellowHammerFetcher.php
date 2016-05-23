<?php

namespace Tagcade\DataSource\YellowHammer;


use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Tagcade\DataSource\PartnerFetcherAbstract;
use Tagcade\DataSource\PartnerParamInterface;
use Tagcade\DataSource\YellowHammer\Page\HomePage;
use Tagcade\DataSource\YellowHammer\Page\ReportingPage;

class YellowHammerFetcher extends PartnerFetcherAbstract implements YellowHammerFetcherInterface
{
    public function getAllData(PartnerParamInterface $params, RemoteWebDriver $driver)
    {
        // Step 1: login
        $this->logger->info('entering login page');
        $homePage = new HomePage($driver, $this->logger);
        $homePage->doLogin($params->getUsername(), $params->getPassword());

        $driver->wait()->until(WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::id('metrics_legend')));
        // Step 2: view report
        $this->logger->info('entering download report page');
        $reportingPage = new ReportingPage($driver, $this->logger);
        $reportingPage->setDownloadFileHelper($this->downloadFileHelper);
        if (!$reportingPage->isCurrentUrl()) {
            $reportingPage->navigate();
        }

        $driver->wait()->until(WebDriverExpectedCondition::titleContains('Reporting'));

        $this->logger->info('start downloading reports');
        $reportingPage->getAllTagReports($params->getStartDate(), $params->getEndDate());
        $this->logger->info('finishing downloading reports');
    }
}