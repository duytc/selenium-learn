<?php


namespace Tagcade\Service\Fetcher\Fetchers\Lkqd;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Tagcade\Service\Fetcher\Fetchers\Lkqd\Page\HomePage;
use Tagcade\Service\Fetcher\Fetchers\Lkqd\Page\ReportPage;
use Tagcade\Service\Fetcher\PartnerFetcherAbstract;
use Tagcade\Service\Fetcher\PartnerParamInterface;


class LkqdFetcher extends PartnerFetcherAbstract implements LkqdFetcherInterface
{

    /**
     * download report data based on given params and save report files to pre-configured directory
     * @param PartnerParamInterface $params
     * @param RemoteWebDriver $driver
     * @return void
     */
    public function getAllData(PartnerParamInterface $params, RemoteWebDriver $driver)
    {
        // Step 1: login
        $this->logger->info('enter login page');
        $homePage = new HomePage($driver, $this->logger);
        $isLogin = $homePage->doLogin($params->getUsername(), $params->getPassword());

        if(false == $isLogin) {
            $this->logger->warning('Login system failed');
            return;
        }

        // Step 2: download reports
        $this->logger->debug('enter download report page');
        $deliveryReportPage = new ReportPage($driver, $this->logger);
        $deliveryReportPage->setDownloadFileHelper($this->getDownloadFileHelper());
        $deliveryReportPage->setConfig($params->getConfig());

        $this->logger->info('start downloading reports');
        $deliveryReportPage->getAllTagReports($params->getStartDate(), $params->getEndDate());
    }
}