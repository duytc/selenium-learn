<?php

namespace Tagcade\DataSource\Sovrn;

use Facebook\WebDriver\Remote\RemoteWebDriver;
use Tagcade\DataSource\PartnerFetcherAbstract;
use Tagcade\DataSource\Sovrn\Page\EarningPage;
use Tagcade\DataSource\Sovrn\Page\HomePage;
use Tagcade\DataSource\DefyMedia\Page\ReportingPage;
use Tagcade\DataSource\PartnerParamInterface;

class SovrnFetcher extends PartnerFetcherAbstract implements SovrnFetcherInterface
{
    public function getAllData(PartnerParamInterface $params, RemoteWebDriver $driver)
    {
        // Step 1: login
        $this->logger->info('entering login page');
        $homePage = new HomePage($driver, $this->logger);
        $isLogin = $homePage->doLogin($params->getUsername(), $params->getPassword());
        if(false == $isLogin) {
            $this->logger->warning('Login system failed!');
            return;
        }

        usleep(10);

        $this->logger->debug('entering download report page');
        $earningPage = new EarningPage($driver, $this->logger);
        $earningPage->setDownloadFileHelper($this->downloadFileHelper);
        $earningPage->setConfig($params->getConfig());

        if (!$earningPage->isCurrentUrl()) {
            $earningPage->navigate();
        }

        $this->logger->info('start downloading reports');
        $earningPage->getAllTagReports($params->getStartDate(), $params->getEndDate());
        $this->logger->info('finish downloading reports');
    }
}