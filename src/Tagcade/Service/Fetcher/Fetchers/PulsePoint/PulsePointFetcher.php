<?php

namespace Tagcade\Service\Fetcher\Fetchers\PulsePoint;


use Facebook\WebDriver\Remote\RemoteWebDriver;
use Tagcade\Service\Fetcher\PartnerFetcherAbstract;
use Tagcade\Service\Fetcher\Params\PartnerParamInterface;
use Tagcade\Service\Fetcher\Fetchers\PulsePoint\Page\LoginPage;
use Tagcade\Service\Fetcher\Fetchers\PulsePoint\Page\ReportPage;

class PulsePointFetcher extends PartnerFetcherAbstract implements PulsePointFetcherInterface
{
    /**
     * @param PartnerParamInterface $params
     * @param RemoteWebDriver $driver
     */
    public function getAllData(PartnerParamInterface $params, RemoteWebDriver $driver)
    {
        $this->logger->info('enter login page');
        $loginPage = new LoginPage($driver, $this->logger);
        $isLogin = $loginPage->login($params->getUsername(), $params->getPassword());

        if (false == $isLogin) {
            $this->logger->warning('Login system failed!');
            return;
        }
        sleep(5);
        $this->logger->debug('enter download report page');
        $reportPage = new ReportPage($driver, $this->logger);
        $reportPage->setDownloadFileHelper($this->downloadFileHelper);
        $reportPage->setConfig($params->getConfig());
        if (!$reportPage->isCurrentUrl()) {
            $reportPage->navigate();
        }

        $this->logger->info('start downloading reports for pulse-point');
        $reportPage->getAllTagReports($params->getStartDate(), $params->getEndDate());
        $this->logger->info('finish downloading reports');
    }
}