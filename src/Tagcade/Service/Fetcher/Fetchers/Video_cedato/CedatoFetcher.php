<?php

namespace Tagcade\Service\Fetcher\Fetchers\Video_cedato;

use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Tagcade\Service\Fetcher\Fetchers\Video_cedato\Page\DeliveryReportPage;
use Tagcade\Service\Fetcher\Fetchers\Video_cedato\Page\HomePage;
use Tagcade\Service\Fetcher\PartnerFetcherAbstract;
use Tagcade\Service\Fetcher\PartnerParamInterface;

class CedatoFetcher extends PartnerFetcherAbstract implements CedatoFetcherInterface
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
        // Step 1: login
        $this->logger->info('enter login page');
        $homePage = new HomePage($driver, $this->logger);
        $isLogin = $homePage->doLogin($params->getUsername(), $params->getPassword());

        if (false == $isLogin) {
            $this->logger->warning('Login system failed');
            return;
        }

        $this->logger->info('end logging in');

        usleep(10);

        $this->logger->debug('enter download report page');
        $deliveryReportPage = new DeliveryReportPage($driver, $this->logger);
        $deliveryReportPage->setDownloadFileHelper($this->getDownloadFileHelper());
        $deliveryReportPage->setConfig($params->getConfig());

        $deliveryReportPage->navigateToReportPage($params->getReportType());

        $driver->wait()->until(
            WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::id('daterange')),
            'Cannot find date range element in report page'
        );

        $this->logger->info('Start downloading reports');
        $deliveryReportPage->getAllTagReports($params->getStartDate(), $params->getEndDate());
        $this->logger->info('Finish downloading reports');
    }
}