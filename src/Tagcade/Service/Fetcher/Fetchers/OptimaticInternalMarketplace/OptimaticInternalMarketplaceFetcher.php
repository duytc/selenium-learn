<?php

namespace Tagcade\Service\Fetcher\Fetchers\OptimaticInternalMarketplace;

use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Psr\Log\LoggerInterface;
use Tagcade\Service\Fetcher\Fetchers\OptimaticInternalMarketplace\Page\DeliveryReportPage;
use Tagcade\Service\Fetcher\Fetchers\OptimaticInternalMarketplace\Page\HomePage;
use Tagcade\Service\Fetcher\Params\OptimaticInternalMarketplace\OptimaticInternalMarketplacePartnerParamsInterface;
use Tagcade\Service\Fetcher\Params\PartnerParamInterface;
use Tagcade\Service\Fetcher\PartnerFetcherAbstract;

class OptimaticInternalMarketplaceFetcher extends PartnerFetcherAbstract implements OptimaticInternalMarketplaceFetcherInterface
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
        if (!$params instanceof OptimaticInternalMarketplacePartnerParamsInterface) {
            $this->logger->notice('expected OptimaticInternalMarketplacePartnerParams');
            return;
        }

        $this->logger->debug('enter download report page');
        $deliveryReportPage = new DeliveryReportPage($driver, $this->logger);
        $deliveryReportPage->setDownloadFileHelper($this->getDownloadFileHelper());
        $deliveryReportPage->setConfig($params->getConfig());

        /* navigate to report page from dashboard page */
        $deliveryReportPage->navigateToReportPage($params->getReportType());

        $driver->wait()->until(
            WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::id('dp_placements')),
            'Cannot find  dp_placements element in report page'
        );

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