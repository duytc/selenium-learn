<?php

namespace Tagcade\DataSource\Across33;

use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Tagcade\DataSource\Across33\Page\DeliveryReportPage;
use Tagcade\DataSource\Across33\Page\HomePage;
use Tagcade\DataSource\PartnerFetcherAbstract;
use Tagcade\DataSource\PartnerParamInterface;

class Across33Fetcher extends PartnerFetcherAbstract implements Across33FetcherInterface
{
    public function getAllData(PartnerParamInterface $params, RemoteWebDriver $driver)
    {
        // Step 1: login
        $this->logger->info('enter login page');
        $homePage = new HomePage($driver, $this->logger);
        $homePage->doLogin($params->getUsername(), $params->getPassword());
        $this->logger->info('end logging in');

        usleep(10);

        $this->logger->debug('enter download report page');
        $deliveryReportPage = new DeliveryReportPage($driver, $this->logger);
        $deliveryReportPage->setDownloadFileHelper($this->getDownloadFileHelper());

        if (!$deliveryReportPage->isCurrentUrl()) {
            $deliveryReportPage->navigate();
        }

        $driver->wait()->until(
            WebDriverExpectedCondition::titleContains('Publisher Tools')
        );

        $this->logger->info('Start downloading reports');
        $deliveryReportPage->getAllTagReports($params->getStartDate(), $params->getEndDate());
        $this->logger->info('Finish downloading reports');
    }
}