<?php


namespace Tagcade\Service\Fetcher\Fetchers\CpmBase;


use Facebook\WebDriver\Remote\RemoteWebDriver;
use Psr\Log\LoggerInterface;
use Tagcade\Service\Fetcher\Fetchers\CpmBase\Page\HomePage;
use Tagcade\Service\Fetcher\Fetchers\CpmBase\Page\ReportingPage;
use Tagcade\Service\Fetcher\Pages\AbstractHomePage;
use Tagcade\Service\Fetcher\Params\PartnerParamInterface;
use Tagcade\Service\Fetcher\PartnerFetcherAbstract;

class CpmBaseFetcher extends PartnerFetcherAbstract implements CpmBaseFetcherInterface
{
    /**
     * @param PartnerParamInterface $params
     * @param RemoteWebDriver $driver
     */
    public function getAllData(PartnerParamInterface $params, RemoteWebDriver $driver)
    {
        // usleep(10);

        $this->logger->debug('Enter download report page');
        $deliveryReportPage = new ReportingPage($driver, $this->logger);
        $deliveryReportPage->setDownloadFileHelper($this->downloadFileHelper);
        $deliveryReportPage->setConfig($params->getConfig());

        if (!$deliveryReportPage->isCurrentUrl()) {
            $deliveryReportPage->navigate();
        }

        $this->logger->info('Start downloading reports');
        $deliveryReportPage->getAllTagReports($params->getStartDate(), $params->getEndDate());
        $this->logger->info('Finish downloading reports');
    }

    /**
     * get homepage for login
     *
     * @param RemoteWebDriver $driver
     * @param LoggerInterface $logger
     * @return AbstractHomePage
     */
    public function getHomePage(RemoteWebDriver $driver, LoggerInterface $logger)
    {
        return new HomePage($driver, $this->logger);
    }
}