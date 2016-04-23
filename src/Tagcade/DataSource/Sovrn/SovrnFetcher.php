<?php

namespace Tagcade\DataSource\Sovrn;

use Facebook\WebDriver\Remote\RemoteWebDriver;
use Tagcade\DataSource\Sovrn\Page\EarningPage;
use Tagcade\DataSource\Sovrn\Page\HomePage;
use Tagcade\DataSource\DefyMedia\Page\ReportingPage;
use Tagcade\DataSource\PartnerParamInterface;

class SovrnFetcher implements SovrnFetcherInterface
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

        $earningPage = new EarningPage($driver);
        if (!$earningPage->isCurrentUrl()) {
            $earningPage->navigate();
        }

        $earningPage->getAllTagReports($params->getStartDate(), $params->getEndDate());

    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'sovrn';
    }

} 