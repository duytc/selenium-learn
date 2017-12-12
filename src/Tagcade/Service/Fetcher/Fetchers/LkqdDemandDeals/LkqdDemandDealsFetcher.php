<?php

namespace Tagcade\Service\Fetcher\Fetchers\LkqdDemandDeals;

use Exception;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Facebook\WebDriver\WebDriverWait;
use Psr\Log\LoggerInterface;
use Tagcade\Exception\LoginFailException;
use Tagcade\Exception\RuntimeException;
use Tagcade\Service\Fetcher\Fetchers\LkqdDemandDeals\Page\HomePage;
use Tagcade\Service\Fetcher\Fetchers\LkqdDemandDeals\Page\ReportPage;
use Tagcade\Service\Fetcher\Params\Lkqd\LkqdPartnerParams;
use Tagcade\Service\Fetcher\Params\PartnerParamInterface;
use Tagcade\Service\Fetcher\PartnerFetcherAbstract;
use Tagcade\Service\Fetcher\UpdatingPasswordInterface;

class LkqdDemandDealsFetcher extends PartnerFetcherAbstract implements LkqdDemandDealsFetcherInterface, UpdatingPasswordInterface
{
    const REPORT_PAGE_URL = ReportPage::URL;

    /**
     * @inheritdoc
     */
    public function doLogin(PartnerParamInterface $params, RemoteWebDriver $driver, $needToLogin = false)
    {
        /*
         * override code from PartnerFetcherAbstract
         * this for setting $params for homepage
         */

        $this->logger->info(sprintf('Entering login page for integration %s', $params->getIntegrationCName()));

        if (!$needToLogin) {
            return;
        }

        /** @var HomePage $homePage */
        $homePage = $this->getHomePage($driver, $this->logger);
        $homePage->setPartnerParams($params);
        $homePage->setTagcadeRestClient($this->tagcadeRestClient);
        $isLoggedIn = $homePage->doLogin($params->getUsername(), $params->getPassword());

        if (false == $isLoggedIn) {
            $this->logger->warning(sprintf('Login system failed for integration %s', $params->getIntegrationCName()));

            throw new LoginFailException(
                $params->getPublisherId(),
                $params->getIntegrationCName(),
                $params->getDataSourceId(),
                $params->getStartDate(),
                $params->getEndDate(),
                new \DateTime()
            );
        }
    }

    /**
     * download report data based on given params and save report files to pre-configured directory
     * @param PartnerParamInterface $params
     * @param RemoteWebDriver $driver
     * @return void
     */
    public function getAllData(PartnerParamInterface $params, RemoteWebDriver $driver)
    {
        if (!$params instanceof LkqdPartnerParams) {
            $this->logger->notice('expected LkqdPartnerParams');
            return;
        }

        $this->logger->debug('enter download report page');
        $deliveryReportPage = new ReportPage($driver, $this->logger);
        $deliveryReportPage->setDownloadFileHelper($this->getDownloadFileHelper());
        $deliveryReportPage->setConfig($params->getConfig());

        if (!$deliveryReportPage->isCurrentUrl()) {
            $deliveryReportPage->navigate();
        }

        $driver->manage()->timeouts()->pageLoadTimeout(10);

        // try ignore Updating Password if available
        try {
            if ($driver->findElement(WebDriverBy::cssSelector('#update-password > div > div > div > div.form-box > div.button-box'))) {
                $ignored = HomePage::ignoreUpdatingPassword($driver, $this->logger);
                if (!$ignored) {
                    throw new RuntimeException('could not ignore updating password page, need try again');
                }
            }
        } catch (\Exception $e) {
            // do nothing
        }

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

    /**
     * @inheritdoc
     */
    public function ignoreUpdatingPassword(RemoteWebDriver $driver)
    {
        try {
            $this->logger->debug('Password expiry, click update later');
            $driver->findElement(WebDriverBy::cssSelector('#update-password > div > div > div > div.form-box > div.bottom-box > a'))->click();

            $waitDriver = new WebDriverWait($driver, 60);
            $waitDriver->until(WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::id('reports')));
        } catch (Exception $e) {
            return false;
        }

        return true;
    }
}