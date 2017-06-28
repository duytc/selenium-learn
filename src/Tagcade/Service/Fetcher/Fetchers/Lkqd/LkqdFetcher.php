<?php

namespace Tagcade\Service\Fetcher\Fetchers\Lkqd;

use Exception;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Facebook\WebDriver\WebDriverWait;
use Psr\Log\LoggerInterface;
use Tagcade\Service\Fetcher\Fetchers\Lkqd\Page\HomePage;
use Tagcade\Service\Fetcher\Fetchers\Lkqd\Page\ReportPage;
use Tagcade\Service\Fetcher\Params\Lkqd\LkqdPartnerParams;
use Tagcade\Service\Fetcher\Params\PartnerParamInterface;
use Tagcade\Service\Fetcher\PartnerFetcherAbstract;
use Tagcade\Service\Fetcher\UpdatingPasswordInterface;

class LkqdFetcher extends PartnerFetcherAbstract implements LkqdFetcherInterface, UpdatingPasswordInterface
{
    const REPORT_PAGE_URL = 'https://ui.lkqd.com/reports';

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

    public function ignoreUpdatingPassword(RemoteWebDriver $driver)
    {
        try {
            if ($driver->findElement(WebDriverBy::cssSelector('#update-password > div > div > div > div.form-box > div.button-box'))) {
                $this->logger->debug('Password expiry, click update later');

                $driver->findElement(WebDriverBy::cssSelector('#update-password > div > div > div > div.form-box > div.bottom-box > a'))->click();
            }

            return true;
            //element not found
        } catch (Exception $e) {

        }
        $waitDriver = new WebDriverWait($driver, 60);
        try {
            $waitDriver->until(WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::id('reports')));

            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}