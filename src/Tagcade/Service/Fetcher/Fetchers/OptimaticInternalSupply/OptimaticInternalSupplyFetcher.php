<?php

namespace Tagcade\Service\Fetcher\Fetchers\OptimaticInternalSupply;

use Facebook\WebDriver\Remote\RemoteWebDriver;
use Psr\Log\LoggerInterface;
use Tagcade\Service\Fetcher\Fetchers\OptimaticInternalSupply\Page\DeliveryReportPage;
use Tagcade\Service\Fetcher\Fetchers\OptimaticInternalSupply\Page\HomePage;
use Tagcade\Service\Fetcher\Params\OptimaticInternalSupply\OptimaticInternalSupplyPartnerParamsInterface;
use Tagcade\Service\Fetcher\Params\PartnerParamInterface;
use Tagcade\Service\Fetcher\PartnerFetcherAbstract;

class OptimaticInternalSupplyFetcher extends PartnerFetcherAbstract implements OptimaticInternalSupplyFetcherInterface
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
        if (!$params instanceof OptimaticInternalSupplyPartnerParamsInterface) {
            $this->logger->notice('expected OptimaticExternalPartnerParams');
            return;
        }

        $this->logger->debug('enter download report page');
        $deliveryReportPage = new DeliveryReportPage($driver, $this->logger);
        $deliveryReportPage->setDownloadFileHelper($this->getDownloadFileHelper());
        $deliveryReportPage->setConfig($params->getConfig());

        // common step of other fetchers,
        // but here not need, it already in DeliveryReportPage
        //if (!$deliveryReportPage->isCurrentUrl()) {
        //    $deliveryReportPage->navigate();
        //}

        $this->logger->info('Start downloading reports');
        $deliveryReportPage->getAllTagReports($params);
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