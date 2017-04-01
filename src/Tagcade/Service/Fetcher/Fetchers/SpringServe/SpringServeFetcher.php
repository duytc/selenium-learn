<?php

namespace Tagcade\Service\Fetcher\Fetchers\SpringServe;

use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverElement;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Tagcade\Service\Fetcher\Fetchers\SpringServe\Page\DeliveryReportingPage;
use Tagcade\Service\Fetcher\Fetchers\SpringServe\Page\HomePage;
use Tagcade\Service\Fetcher\PartnerFetcherAbstract;
use Tagcade\Service\Fetcher\PartnerParamInterface;
use Facebook\WebDriver\WebDriverBy;

class SpringServeFetcher extends PartnerFetcherAbstract implements SpringServeFetcherInterface
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

        if (!$login) {
            $this->logger->warning('Login system failed');
            return;
        }

        $this->logger->info('end logging in');

        usleep(5);

        $this->logger->debug('enter download report page');
        $deliveryReportPage = new DeliveryReportingPage($driver, $this->logger);

        $deliveryReportPage->setDownloadFileHelper($this->getDownloadFileHelper());
        $deliveryReportPage->setConfig($params->getConfig());

        $driver->wait()->until(
            WebDriverExpectedCondition::titleContains('SpringServe')
        );

        /**
         * @var WebDriverElement $userAccountChosen
         */
        $userAccountChosen = $driver->findElement(WebDriverBy::id('user_account_id_chosen'));
        $userAccountChosen->click();

        /**
         * @var WebDriverElement[] $liElements
         */
        $liElements = $userAccountChosen->findElements(WebDriverBy::tagName('li'));
        $needElement = null;

        foreach ($liElements as $liElement) {
            if ($liElement->getText() == 'Division D (Supply)') {
                $needElement = $liElement;
                $needElement->click();
                break;
            }
        }

        if ($needElement instanceof WebDriverElement) {
            $driver->navigate()->to(DeliveryReportingPage::URL);

            $driver->wait()->until(
                WebDriverExpectedCondition::titleContains('SpringServe Reports')
            );

            $this->logger->info('Start downloading reports');
            $deliveryReportPage->getAllTagReports($params->getStartDate(), $params->getEndDate());
            $this->logger->info('Finish downloading reports');
        } else {
            $this->logger->info('Not found Division D partner');
        }
    }
}