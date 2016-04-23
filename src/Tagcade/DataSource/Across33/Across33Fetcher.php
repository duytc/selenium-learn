<?php

namespace Tagcade\DataSource\Across33;

use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Tagcade\DataSource\Across33\Page\DeliveryReportPage;
use Tagcade\DataSource\Across33\Page\HomePage;
use Tagcade\DataSource\PartnerParamInterface;

class Across33Fetcher implements Across33FetcherInterface
{
    public function getAllData(PartnerParamInterface $params, RemoteWebDriver $driver)
    {
        // Step 1: login
        $homePage = new HomePage($driver);
        if (!$homePage->isCurrentUrl()) {
            $homePage->navigate();
        }

        $homePage->doLogin($params->getUsername(), $params->getPassword());

        usleep(10);


        $deliveryReportPage = new DeliveryReportPage($driver);
        if (!$deliveryReportPage->isCurrentUrl()) {
            $deliveryReportPage->navigate();
        }

        $driver->wait()->until(
            WebDriverExpectedCondition::titleContains('Publisher Tools')
        );

        $deliveryReportPage->getAllTagReports($params->getStartDate(), $params->getEndDate());

//        $reportingPage = new ReportingPage($driver);
//        if (!$reportingPage->isCurrentUrl()) {
//            $reportingPage->navigate();
//        }
//
//        $reportingPage->getAllTagReports($params->getStartDate(), $params->getEndDate());

    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'across33';
    }

} 