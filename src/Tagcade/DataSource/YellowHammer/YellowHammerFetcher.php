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
        $homePage = new HomePage($driver, $this->logger);
        if (!$homePage->isCurrentUrl()) {
            $homePage->navigate();
        }

        $homePage->doLogin($params->getUsername(), $params->getPassword());

        $driver->wait()->until(WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::id('metrics_legend')));
        // Step 2: view report
        $reportingPage = new ReportingPage($driver, $this->logger);
        if (!$reportingPage->isCurrentUrl()) {
            $reportingPage->navigate();
        }

        $driver->wait()->until(WebDriverExpectedCondition::titleContains('Reporting'));

        $reportingPage->getAllTagReports($params->getStartDate(), $params->getEndDate());
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'yellow-hammer';
    }


} 