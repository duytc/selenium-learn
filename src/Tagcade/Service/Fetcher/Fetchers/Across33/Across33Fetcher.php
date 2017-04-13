<?php

namespace Tagcade\Service\Fetcher\Fetchers\Across33;

use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Psr\Log\LoggerInterface;
use Tagcade\Service\Fetcher\Fetchers\Across33\Page\DeliveryReportPage;
use Tagcade\Service\Fetcher\Fetchers\Across33\Page\HomePage;
use Tagcade\Service\Fetcher\Params\PartnerParamInterface;
use Tagcade\Service\Fetcher\PartnerFetcherAbstract;

class Across33Fetcher extends PartnerFetcherAbstract implements Across33FetcherInterface
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
        $this->logger->debug('enter download report page');
        $deliveryReportPage = new DeliveryReportPage($driver, $this->logger);
        $deliveryReportPage->setDownloadFileHelper($this->getDownloadFileHelper());
        $deliveryReportPage->setConfig($params->getConfig());


        if (!$deliveryReportPage->isCurrentUrl()) {
            $deliveryReportPage->navigate();
        }

        $driver->wait()->until(
            WebDriverExpectedCondition::titleContains('Publisher Tools'),
            'Login Fail'
        );

        $this->logger->info('Start downloading reports');
        $deliveryReportPage->getAllTagReports($params->getStartDate(), $params->getEndDate());
        $this->logger->info('Finish downloading reports');
    }

    /**
     * @inheritdoc
     */
    function getHomePage(RemoteWebDriver $driver, LoggerInterface $logger)
    {
        return $homePage = new HomePage($driver, $this->logger);
    }
}