<?php


namespace Tagcade\Service\Fetcher\Fetchers\Adtech;


use Facebook\WebDriver\Remote\RemoteWebDriver;
use Psr\Log\LoggerInterface;
use Tagcade\Service\Fetcher\Fetchers\Adtech\Page\HomePage;
use Tagcade\Service\Fetcher\Fetchers\Adtech\Page\ReportingPage;
use Tagcade\Service\Fetcher\Pages\AbstractHomePage;
use Tagcade\Service\Fetcher\Params\PartnerParamInterface;
use Tagcade\Service\Fetcher\PartnerFetcherAbstract;

class AdtechFetcher extends PartnerFetcherAbstract implements AdtechFetcherInterface
{
    /**
     * @param PartnerParamInterface $params
     * @param RemoteWebDriver $driver
     */
    public function getAllData(PartnerParamInterface $params, RemoteWebDriver $driver)
    {
        // sleep(5);

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

    /**
     * get homepage for login
     *
     * @param RemoteWebDriver $driver
     * @param LoggerInterface $logger
     * @return AbstractHomePage
     */
    function getHomePage(RemoteWebDriver $driver, LoggerInterface $logger)
    {
        return new HomePage($driver, $this->logger);
    }
}