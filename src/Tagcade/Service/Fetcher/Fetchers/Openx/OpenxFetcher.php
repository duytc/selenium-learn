<?php

namespace Tagcade\Service\Fetcher\Fetchers\Openx;

use Facebook\WebDriver\Remote\RemoteWebDriver;
use Tagcade\Service\Fetcher\Fetchers\Openx\Page\ReportingPage;
use Tagcade\Service\Fetcher\Fetchers\Openx\Page\HomePage;
use Tagcade\Service\Fetcher\PartnerFetcherAbstract;
use Tagcade\Service\Fetcher\Params\PartnerParamInterface;

class OpenxFetcher extends PartnerFetcherAbstract implements OpenxFetcherInterface
{
    /**
     * @param PartnerParamInterface $params
     * @param RemoteWebDriver $driver
     */
    public function getAllData(PartnerParamInterface $params, RemoteWebDriver $driver)
    {
        // Step 1: login
        $this->logger->info('enter login page');
        $homePage = new HomePage($driver, $this->logger);
        $login = $homePage->doLogin($params->getUsername(), $params->getPassword());

        if (false == $login) {
            $this->logger->warning('Login system fail');
            return;
        }

        $this->logger->info('end logging in');

        usleep(10);

        $this->logger->debug('enter download report page');
        $deliveryReportPage = new ReportingPage($driver, $this->logger);

        if (!$deliveryReportPage->isCurrentUrl()) {
            $deliveryReportPage->navigate();
        }

        $deliveryReportPage->setDownloadFileHelper($this->getDownloadFileHelper());
        $deliveryReportPage->setConfig($params->getConfig());


        if (!$deliveryReportPage->isCurrentUrl()) {
            $deliveryReportPage->navigate();
        }

        $this->logger->info('Start downloading reports');
        $deliveryReportPage->getAllTagReports($params->getStartDate(), $params->getEndDate());
        $this->logger->info('Finish downloading reports');
    }
}