<?php

namespace Tagcade\Service\Fetcher\Fetchers\Technorati;

use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Tagcade\Service\Fetcher\Fetchers\Technorati\Page\ReportingPage;
use Tagcade\Service\Fetcher\Fetchers\Technorati\Page\HomePage;
use Tagcade\Service\Fetcher\PartnerFetcherAbstract;
use Tagcade\Service\Fetcher\Params\PartnerParamInterface;
use Facebook\WebDriver\WebDriverBy;

class TechnoratiFetcher extends PartnerFetcherAbstract implements TechnoratiFetcherInterface
{
    /**
     * @param PartnerParamInterface $params
     * @param RemoteWebDriver $driver
     * @throws \Facebook\WebDriver\Exception\NoSuchElementException
     * @throws \Facebook\WebDriver\Exception\TimeOutException
     * @throws null
     */
    public function getAllData(PartnerParamInterface $params, RemoteWebDriver $driver)
    {
        // Step 1: login
        $this->logger->info('enter login page');
        $homePage = new HomePage($driver, $this->logger);
        usleep(10);
        $login = $homePage->doLogin($params->getUsername(), $params->getPassword());

        if(!$login) {
            $this->logger->warning('Login system failed');
            return;
        }

        $this->logger->info('end logging in');

        usleep(10);

        $this->logger->debug('enter download report page');
        $deliveryReportPage = new ReportingPage($driver, $this->logger);

        $deliveryReportPage->setDownloadFileHelper($this->getDownloadFileHelper());
        $deliveryReportPage->setConfig($params->getConfig());

        $driver->wait()->until(
            WebDriverExpectedCondition::titleContains('Contango - Dashboard')
        );

        if (!$deliveryReportPage->isCurrentUrl()) {
            $driver->findElement(WebDriverBy::cssSelector('#leftside > div.menu-items.ng-scope > div:nth-child(7) > span.menu-text'))->click();
            $driver->findElement(WebDriverBy::cssSelector('#leftside > div.menu-items.ng-scope > div.menu-item.open > div > div.submenu-item.ng-scope > span'))->click();
        }

        $driver->wait()->until(
            WebDriverExpectedCondition::titleContains('Contango - Reports')
        );

        $this->logger->info('Start downloading reports');
        $deliveryReportPage->getAllTagReports($params->getStartDate(), $params->getEndDate());
        $this->logger->info('Finish downloading reports');
    }
}