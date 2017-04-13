<?php

namespace Tagcade\Service\Fetcher\Fetchers\Media;

use Facebook\WebDriver\Remote\RemoteWebDriver;
use Psr\Log\LoggerInterface;
use Tagcade\Service\Fetcher\Fetchers\Media\Page\HomePage;
use Tagcade\Service\Fetcher\Fetchers\Media\Page\ReportingPage;
use Tagcade\Service\Fetcher\Params\PartnerParamInterface;
use Tagcade\Service\Fetcher\PartnerFetcherAbstract;

class MediaNetFetcher extends PartnerFetcherAbstract implements MediaNetFetcherInterface
{
    /**
     * @param PartnerParamInterface $params
     * @param RemoteWebDriver $driver
     */
    public function getAllData(PartnerParamInterface $params, RemoteWebDriver $driver)
    {
        $this->logger->info('Enter download report page');
        $reportingPage = new ReportingPage($driver, $this->logger);
        $reportingPage->setDownloadFileHelper($this->getDownloadFileHelper());
        $reportingPage->setConfig($params->getConfig());

        if (!$reportingPage->isCurrentUrl()) {
            $reportingPage->navigate();
        }

        $this->logger->info('Start downloading reports');
        $reportingPage->getAllTagReports($params->getStartDate(), $params->getEndDate());
        $this->logger->info('Finish downloading reports');
    }

    /**
     * @inheritdoc
     */
    public function getHomePage(RemoteWebDriver $driver, LoggerInterface $logger)
    {
        return new HomePage($driver, $this->logger);
    }
}