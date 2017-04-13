<?php

namespace Tagcade\Service\Fetcher\Fetchers\Komoona;


use Facebook\WebDriver\Remote\RemoteWebDriver;
use Psr\Log\LoggerInterface;
use Tagcade\Service\Fetcher\Fetchers\Komoona\Page\HomePage;
use Tagcade\Service\Fetcher\Fetchers\Komoona\Page\IncomeReportPage;
use Tagcade\Service\Fetcher\Params\PartnerParamInterface;
use Tagcade\Service\Fetcher\PartnerFetcherAbstract;

class KomoonaFetcher extends PartnerFetcherAbstract implements KomoonaFetcherInterface
{
    /**
     * @param PartnerParamInterface $params
     * @param RemoteWebDriver $driver
     */
    public function getAllData(PartnerParamInterface $params, RemoteWebDriver $driver)
    {
        $incomeReportPage = new IncomeReportPage($driver, $this->logger);
        $incomeReportPage->setDownloadFileHelper($this->downloadFileHelper);
        $incomeReportPage->setConfig($params->getConfig());

        $this->logger->debug(sprintf('Navigating to report page %s', $incomeReportPage->getPageUrl()));
        $incomeReportPage->navigate();

        $this->logger->info(sprintf('Getting report for page %s', $incomeReportPage->getPageUrl()));
        $incomeReportPage->getAllTagReports($params->getStartDate(), $params->getEndDate());
    }

    /**
     * @inheritdoc
     */
    public function getHomePage(RemoteWebDriver $driver, LoggerInterface $logger)
    {
        return new HomePage($driver, $this->logger);
    }
}