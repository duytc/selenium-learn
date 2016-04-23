<?php

namespace Tagcade\DataSource\Komoona;


use Facebook\WebDriver\Remote\RemoteWebDriver;
use Tagcade\DataSource\Komoona\Page\HomePage;
use Tagcade\DataSource\Komoona\Page\IncomeReportPage;
use Tagcade\DataSource\PartnerParamInterface;

class KomoonaFetcher implements KomoonaFetcherInterface
{
    const NAME = 'komoona';

    public function getAllData(PartnerParamInterface $params, RemoteWebDriver $driver)
    {
        // Step 1: login
        $homePage = new HomePage($driver);
        if (!$homePage->isCurrentUrl()) {
            $homePage->navigate();
        }

        $homePage->doLogin($params->getUsername(), $params->getPassword());

        // Step 2: view report
        $incomeReportPage = new IncomeReportPage($driver);
        $incomeReportPage->navigate();

        $incomeReportPage->getAllTagReports($params->getStartDate(), $params->getEndDate());
    }

    /**
     * @return string
     */
    public function getName()
    {
        return self::NAME;
    }


} 