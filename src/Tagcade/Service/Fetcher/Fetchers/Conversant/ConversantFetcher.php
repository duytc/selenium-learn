<?php

namespace Tagcade\Service\Fetcher\Fetchers\Conversant;

use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Tagcade\Service\Fetcher\Fetchers\Conversant\Page\ReportingPage;
use Tagcade\Service\Fetcher\Fetchers\Conversant\Page\HomePage;
use Tagcade\Service\Fetcher\PartnerFetcherAbstract;
use Tagcade\Service\Fetcher\PartnerParamInterface;

class ConversantFetcher extends PartnerFetcherAbstract implements ConversantFetcherInterface
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
        $login = $homePage->doLogin($params->getUsername(), $params->getPassword());

        if(!$login) {
            $this->logger->warning('Login system failed');
            return;
        }

        $this->logger->info('end logging in');

        usleep(10);

        $this->logger->debug('enter download report page');
        $deliveryReportPage = new ReportingPage($driver, $this->logger);

        $deliveryReportPage->setDownloadFileHelper($this->getDownloadFileHelper());
        $deliveryReportPage->setConfig($params->getConfig());


        if (!$deliveryReportPage->isCurrentUrl()) {
            $deliveryReportPage->navigate();
        }

        $driver->wait()->until(
            WebDriverExpectedCondition::titleContains('Publisher UI - Earnings History')
        );

        $this->logger->info('Start downloading reports');
        $deliveryReportPage->getAllTagReports($params->getStartDate(), $params->getEndDate());
        $this->logger->info('Finish downloading reports');

        $this->logger->info('logout');
        $deliveryReportPage->logout();
    }
}