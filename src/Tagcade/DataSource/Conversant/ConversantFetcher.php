<?php

namespace Tagcade\DataSource\Conversant;

use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Tagcade\DataSource\Conversant\Page\ReportingPage;
use Tagcade\DataSource\Conversant\Page\HomePage;
use Tagcade\DataSource\PartnerFetcherAbstract;
use Tagcade\DataSource\PartnerParamInterface;

class ConversantFetcher extends PartnerFetcherAbstract implements ConversantFetcherInterface
{
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