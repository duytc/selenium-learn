<?php

namespace Tagcade\Service\Fetcher\Fetchers\YellowHammer;


use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Psr\Log\LoggerInterface;
use Tagcade\Service\Fetcher\Fetchers\YellowHammer\Page\HomePage;
use Tagcade\Service\Fetcher\Fetchers\YellowHammer\Page\ReportingPage;
use Tagcade\Service\Fetcher\Params\PartnerParamInterface;
use Tagcade\Service\Fetcher\PartnerFetcherAbstract;

class YellowHammerFetcher extends PartnerFetcherAbstract implements YellowHammerFetcherInterface
{
    /**
     * @param PartnerParamInterface $params
     * @param RemoteWebDriver $driver
     * @throws \Facebook\WebDriver\Exception\NoSuchElementException
     * @throws \Facebook\WebDriver\Exception\TimeOutException
     * @throws null
     */
    public function getAllData(PartnerParamInterface $params, RemoteWebDriver $driver)
    {
        $driver->wait()->until(WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::id('metrics_legend')));

        // Step 2: view report
        $this->logger->info('entering download report page');
        $reportingPage = new ReportingPage($driver, $this->logger);
        $reportingPage->setDownloadFileHelper($this->downloadFileHelper);
        $reportingPage->setConfig($params->getConfig());

        if (!$reportingPage->isCurrentUrl()) {
            $reportingPage->navigate();
        }

        $driver->wait()->until(WebDriverExpectedCondition::titleContains('Reporting'));

        $this->logger->info('start downloading reports');
        $reportingPage->getAllTagReports($params->getStartDate(), $params->getEndDate());
        $this->logger->info('finishing downloading reports');
    }

    /**
     * @inheritdoc
     */
    public function getHomePage(RemoteWebDriver $driver, LoggerInterface $logger)
    {
        return new HomePage($driver, $this->logger);
    }
}