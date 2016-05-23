<?php

namespace Tagcade\DataSource\PulsePoint;


use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Tagcade\DataSource\Komoona\Page\HomePage;
use Tagcade\DataSource\Komoona\Page\IncomeReportPage;
use Tagcade\DataSource\PartnerFetcherAbstract;
use Tagcade\DataSource\PartnerParamInterface;
use Tagcade\DataSource\PulsePoint\Page\LoginPage;
use Tagcade\DataSource\PulsePoint\Page\ManagerPage;
use Tagcade\DataSource\PulsePoint\Page\ReportPage;
use Tagcade\DataSource\PulsePoint\Widget\DateRangeWidget;
use Tagcade\DataSource\PulsePoint\Widget\ExportButtonWidget;
use Tagcade\DataSource\PulsePoint\Widget\ReportSelectorWidget;
use Tagcade\DataSource\PulsePoint\Widget\ReportTypeWidget;
use Tagcade\DataSource\PulsePoint\Widget\RunButtonWidget;

class PulsePointFetcher extends PartnerFetcherAbstract implements PulsePointFetcherInterface
{
    public function getAllData(PartnerParamInterface $params, RemoteWebDriver $driver)
    {
        $this->logger->info('enter login page');
        $loginPage = new LoginPage($driver, $this->logger);
        $loginPage->login($params->getUsername(), $params->getPassword());
        sleep(5);

        $this->logger->info('enter download report page');
        $reportPage = new ReportPage($driver, $this->logger);
        $reportPage->setDownloadFileHelper($this->downloadFileHelper);
        if (!$reportPage->isCurrentUrl()) {
            $reportPage->navigate();
        }

        $this->logger->info('start downloading reports for pulse-point');
        $reportPage->getAllTagReports($params->getStartDate(), $params->getEndDate());
        $this->logger->info('finish downloading reports');
    }
}