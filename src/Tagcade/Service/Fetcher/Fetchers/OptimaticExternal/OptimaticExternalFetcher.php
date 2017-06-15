<?php

namespace Tagcade\Service\Fetcher\Fetchers\OptimaticExternal;

use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Psr\Log\LoggerInterface;
use Tagcade\Service\Fetcher\Fetchers\OptimaticExternal\Page\DeliveryReportPage;
use Tagcade\Service\Fetcher\Fetchers\OptimaticExternal\Page\HomePage;
use Tagcade\Service\Fetcher\Params\OptimaticExternal\OptimaticExternalPartnerParamsInterface;
use Tagcade\Service\Fetcher\Params\PartnerParamInterface;
use Tagcade\Service\Fetcher\PartnerFetcherAbstract;

class OptimaticExternalFetcher extends PartnerFetcherAbstract implements OptimaticExternalFetcherInterface
{
    const REPORT_PAGE_URL = 'https://publishers.optimatic.com/Portal2/';

    /**
     * @param PartnerParamInterface $params
     * @param RemoteWebDriver $driver
     * @throws \Facebook\WebDriver\Exception\NoSuchElementException
     * @throws \Facebook\WebDriver\Exception\TimeOutException
     * @throws null
     */
    public function getAllData(PartnerParamInterface $params, RemoteWebDriver $driver)
    {
        if (!$params instanceof OptimaticExternalPartnerParamsInterface) {
            $this->logger->error('expected OptimaticExternalPartnerParams');
            return;
        }

        $this->logger->debug('enter download report page');
        $deliveryReportPage = new DeliveryReportPage($driver, $this->logger);
        $deliveryReportPage->setDownloadFileHelper($this->getDownloadFileHelper());
        $deliveryReportPage->setConfig($params->getConfig());

//        $driver->wait()->until(
//            WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::id('dp_placements')),
//            'Cannot find  dp_placements element in report page'
//        );

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