<?php

namespace Tagcade\DataSource\DefyMedia;

use Facebook\WebDriver\Remote\RemoteWebDriver;
use Tagcade\DataSource\DefyMedia\Page\HomePage;
use Tagcade\DataSource\DefyMedia\Page\ReportingPage;
use Tagcade\DataSource\PartnerParamInterface;

class DefyMediaFetcher implements DefyMediaFetcherInterface
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

        $reportingPage = new ReportingPage($driver);
        if (!$reportingPage->isCurrentUrl()) {
            $reportingPage->navigate();
        }

        $reportingPage->getAllTagReports($params->getStartDate(), $params->getEndDate());

    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'defymedia';
    }

} 