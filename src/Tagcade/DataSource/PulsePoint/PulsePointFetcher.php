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
    const NAME = 'pulse-point';

    public function getAllData(PartnerParamInterface $params, RemoteWebDriver $driver)
    {
        $loginPage = new LoginPage($driver, $this->logger);
        if (!$loginPage->isCurrentUrl() && !$loginPage->isLoggedIn()) {
            $loginPage->navigate();
            $driver->wait()->until(WebDriverExpectedCondition::elementToBeClickable(WebDriverBy::id('LoginButton')));
        }


        $loginPage->login($params->getUsername(), $params->getPassword());
        sleep(5);

        $reportPage = new ReportPage($driver, $this->logger);
        if (!$reportPage->isCurrentUrl()) {
            $reportPage->navigate();
        }

        $driver->wait()->until(WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::id('reportDDLContainer')));

        $reportPage->getAllTagReports($params->getStartDate(), $params->getEndDate());

    }

    /**
     * @return string
     */
    public function getName()
    {
        return self::NAME;
    }


} 