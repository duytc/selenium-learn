<?php


namespace Tagcade\Service\Fetcher\Fetchers\CpmBase;


use Facebook\WebDriver\Remote\RemoteWebDriver;
use Tagcade\Service\Fetcher\Fetchers\CpmBase\Page\HomePage;
use Tagcade\Service\Fetcher\Fetchers\CpmBase\Page\ReportingPage;
use Tagcade\Service\Fetcher\PartnerFetcherAbstract;
use Tagcade\Service\Fetcher\PartnerParamInterface;

class CpmBaseFetcher extends PartnerFetcherAbstract implements CpmBaseFetcherInterface
{
    /**
     * @param PartnerParamInterface $params
     * @param RemoteWebDriver $driver
     */
    public function getAllData(PartnerParamInterface $params, RemoteWebDriver $driver)
    {
        // Step 1: login
        $this->logger->debug('Enter login page');
        $homePage = new HomePage($driver, $this->logger);
        $login = $homePage->doLogin($params->getUsername(), $params->getPassword());
        if (false == $login) {
            $this->logger->warning('Login system fail');
            return;
        }
        $this->logger->debug('End logging in');

        usleep(10);

        $this->logger->debug('Enter download report page');
        $deliveryReportPage = new ReportingPage($driver, $this->logger);
        $deliveryReportPage->setDownloadFileHelper($this->downloadFileHelper);
        $deliveryReportPage->setConfig($params->getConfig());

        if (!$deliveryReportPage->isCurrentUrl()) {
            $deliveryReportPage->navigate();
        }

        $this->logger->info('Start downloading reports');
        $deliveryReportPage->getAllTagReports($params->getStartDate(), $params->getEndDate());
        $this->logger->info('Finish downloading reports');
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param mixed $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }
}