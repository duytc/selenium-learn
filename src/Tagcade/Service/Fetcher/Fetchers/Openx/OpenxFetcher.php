<?php

namespace Tagcade\Service\Fetcher\Fetchers\Openx;

use Facebook\WebDriver\Remote\RemoteWebDriver;
use Psr\Log\LoggerInterface;
use Tagcade\Service\Fetcher\Fetchers\Openx\Page\HomePage;
use Tagcade\Service\Fetcher\Fetchers\Openx\Page\ReportingPage;
use Tagcade\Service\Fetcher\Params\PartnerParamInterface;
use Tagcade\Service\Fetcher\PartnerFetcherAbstract;

class OpenxFetcher extends PartnerFetcherAbstract implements OpenxFetcherInterface
{
    /**
     * @param PartnerParamInterface $params
     * @param RemoteWebDriver $driver
     */
    public function getAllData(PartnerParamInterface $params, RemoteWebDriver $driver)
    {
        // usleep(10);

        $this->logger->debug('enter download report page');
        $deliveryReportPage = new ReportingPage($driver, $this->logger);

        if (!$deliveryReportPage->isCurrentUrl()) {
            $deliveryReportPage->navigate();
        }

        $deliveryReportPage->setDownloadFileHelper($this->getDownloadFileHelper());
        $deliveryReportPage->setConfig($params->getConfig());


        if (!$deliveryReportPage->isCurrentUrl()) {
            $deliveryReportPage->navigate();
        }

        $this->logger->info('Start downloading reports');
        $deliveryReportPage->getAllTagReports($params->getStartDate(), $params->getEndDate());
        $this->logger->info('Finish downloading reports');
    }

    /**
     * @inheritdoc
     */
    public function getHomePage(RemoteWebDriver $driver, LoggerInterface $logger)
    {
        return new HomePage($driver, $this->logger);
    }
}