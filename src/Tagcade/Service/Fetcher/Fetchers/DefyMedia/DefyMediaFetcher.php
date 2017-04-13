<?php

namespace Tagcade\Service\Fetcher\Fetchers\DefyMedia;

use Facebook\WebDriver\Remote\RemoteWebDriver;
use Psr\Log\LoggerInterface;
use Tagcade\Service\Fetcher\Fetchers\DefyMedia\Page\HomePage;
use Tagcade\Service\Fetcher\Fetchers\DefyMedia\Page\ReportingPage;
use Tagcade\Service\Fetcher\Params\PartnerParamInterface;
use Tagcade\Service\Fetcher\PartnerFetcherAbstract;

class DefyMediaFetcher extends PartnerFetcherAbstract implements DefyMediaFetcherInterface
{
    /**
     * @param PartnerParamInterface $params
     * @param RemoteWebDriver $driver
     */
    public function getAllData(PartnerParamInterface $params, RemoteWebDriver $driver)
    {
        // usleep(300);

        $this->logger->debug('enter download report page');
        $reportingPage = new ReportingPage($driver, $this->logger);
        $reportingPage->setDownloadFileHelper($this->downloadFileHelper);
        $reportingPage->setConfig($params->getConfig());

        if (!$reportingPage->isCurrentUrl()) {
            $reportingPage->navigate();
        }

        $this->logger->info('start downloading reports');
        $reportingPage->getAllTagReports($params->getStartDate(), $params->getEndDate());
        $this->logger->info('finish downloading reports');
    }

    /**
     * @inheritdoc
     */
    public function getHomePage(RemoteWebDriver $driver, LoggerInterface $logger)
    {
        return new HomePage($driver, $this->logger);
    }
}