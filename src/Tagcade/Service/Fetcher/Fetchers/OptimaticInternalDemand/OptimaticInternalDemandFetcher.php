<?php

namespace Tagcade\Service\Fetcher\Fetchers\OptimaticInternalDemand;

use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Psr\Log\LoggerInterface;
use Tagcade\Service\Fetcher\Fetchers\OptimaticInternalDemand\Page\DeliveryReportPage;
use Tagcade\Service\Fetcher\Fetchers\OptimaticInternalDemand\Page\HomePage;
use Tagcade\Service\Fetcher\Params\OptimaticInternalDemand\OptimaticInternalDemandPartnerParamsInterface;
use Tagcade\Service\Fetcher\Params\PartnerParamInterface;
use Tagcade\Service\Fetcher\PartnerFetcherAbstract;

class OptimaticInternalDemandFetcher extends PartnerFetcherAbstract implements OptimaticInternalDemandFetcherInterface
{
    const REPORT_PAGE_URL = 'https://publishers.optimatic.com/Portal2/default.aspx';

    /**
     * @param PartnerParamInterface $params
     * @param RemoteWebDriver $driver
     * @throws \Facebook\WebDriver\Exception\NoSuchElementException
     * @throws \Facebook\WebDriver\Exception\TimeOutException
     * @throws null
     */
    public function getAllData(PartnerParamInterface $params, RemoteWebDriver $driver)
    {
        if (!$params instanceof OptimaticInternalDemandPartnerParamsInterface) {
            $this->logger->error('expected OptimaticInternalDemandPartnerParams');
            return;
        }

        $this->logger->debug('enter download report page');
        $deliveryReportPage = new DeliveryReportPage($driver, $this->logger);
        $deliveryReportPage->setDownloadFileHelper($this->getDownloadFileHelper());
        $deliveryReportPage->setConfig($params->getConfig());

        /* navigate to report page from dashboard page */
        $deliveryReportPage->navigateToReportPage($params->getReportType());

        $driver->wait()->until(
            WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::id('ContentPlaceHolder_Body_dp_datesearch')),
            'Cannot find  date range element in report page'
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