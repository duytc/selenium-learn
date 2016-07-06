<?php


namespace Tagcade\DataSource\CpmBase;


use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Tagcade\DataSource\CpmBase\Page\HomePage;
use Tagcade\DataSource\CpmBase\Page\ReportingPage;
use Tagcade\DataSource\PartnerFetcherAbstract;
use Tagcade\DataSource\PartnerParamInterface;

class CpmBaseFetcher extends PartnerFetcherAbstract implements CpmBaseFetcherInterface {

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