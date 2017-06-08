<?php

namespace Tagcade\Service\Fetcher\Fetchers\VertaInternal;

use Facebook\WebDriver\Remote\RemoteWebDriver;
use Psr\Log\LoggerInterface;
use Tagcade\Service\Fetcher\Fetchers\VertaInternal\Page\DeliveryReportingPage;
use Tagcade\Service\Fetcher\Fetchers\VertaInternal\Page\HomePage;
use Tagcade\Service\Fetcher\Pages\AbstractHomePage;
use Tagcade\Service\Fetcher\Params\PartnerParamInterface;
use Tagcade\Service\Fetcher\PartnerFetcherAbstract;

class VertaInternalFetcher extends PartnerFetcherAbstract implements VertaInternalFetcherInterface
{
    const REPORT_PAGE_URL = 'https://ssp.vertamedia.com/pages/reports';
    /**
     * @param PartnerParamInterface $params
     * @param RemoteWebDriver $driver
     * @throws \Facebook\WebDriver\Exception\NoSuchElementException
     * @throws \Facebook\WebDriver\Exception\TimeOutException
     * @throws null
     */
    public function getAllData(PartnerParamInterface $params, RemoteWebDriver $driver)
    {
        $this->logger->debug('enter download report page');
        $deliveryReportPage = new DeliveryReportingPage($driver, $this->logger);
        $deliveryReportPage->setDownloadFileHelper($this->getDownloadFileHelper());
        $deliveryReportPage->setConfig($params->getConfig());

        $this->logger->info('Start downloading reports');
        $deliveryReportPage->getAllTagReports($params);
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
        return new HomePage($driver, $logger);
    }
}