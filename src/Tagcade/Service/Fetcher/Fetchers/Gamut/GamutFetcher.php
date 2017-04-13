<?php

namespace Tagcade\Service\Fetcher\Fetchers\Gamut;

use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Psr\Log\LoggerInterface;
use Tagcade\Service\Fetcher\Fetchers\Gamut\Page\DeliveryReportPage;
use Tagcade\Service\Fetcher\Fetchers\Gamut\Page\HomePage;
use Tagcade\Service\Fetcher\Params\PartnerParamInterface;
use Tagcade\Service\Fetcher\PartnerFetcherAbstract;

class GamutFetcher extends PartnerFetcherAbstract implements GamutFetcherInterface
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
        // usleep(10);

        $this->logger->debug('enter download report page');
        $deliveryReportPage = new DeliveryReportPage($driver, $this->logger);
        $deliveryReportPage->setDownloadFileHelper($this->getDownloadFileHelper());
        $deliveryReportPage->setConfig($params->getConfig());


        if (!$deliveryReportPage->isCurrentUrl()) {
            $deliveryReportPage->navigate();
        }

        $driver->wait()->until(
            WebDriverExpectedCondition::titleContains('Create Custom Report'),
            'Login Fail'
        );

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