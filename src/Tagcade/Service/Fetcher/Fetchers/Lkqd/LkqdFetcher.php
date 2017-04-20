<?php


namespace Tagcade\Service\Fetcher\Fetchers\Lkqd;


use Facebook\WebDriver\Remote\RemoteWebDriver;
use Psr\Log\LoggerInterface;
use Tagcade\Service\Fetcher\Fetchers\Lkqd\Page\HomePage;
use Tagcade\Service\Fetcher\Fetchers\Lkqd\Page\ReportPage;
use Tagcade\Service\Fetcher\Params\Lkqd\LkqdPartnerParams;
use Tagcade\Service\Fetcher\Params\PartnerParamInterface;
use Tagcade\Service\Fetcher\PartnerFetcherAbstract;

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
        if (!$params instanceof LkqdPartnerParams) {
            $this->logger->error('expected LkqdPartnerParams');
            return;
        }

        $this->logger->debug('enter download report page');
        $deliveryReportPage = new ReportPage($driver, $this->logger);
        $deliveryReportPage->setDownloadFileHelper($this->getDownloadFileHelper());
        $deliveryReportPage->setConfig($params->getConfig());

        $this->logger->info('start downloading reports');
        $deliveryReportPage->getAllTagReports($params);
    }

    /**
     * @inheritdoc
     */
    public function getHomePage(RemoteWebDriver $driver, LoggerInterface $logger)
    {
        return new HomePage($driver, $this->logger);
    }
}