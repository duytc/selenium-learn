<?php

namespace Tagcade\Service\Fetcher\Fetchers\SpringServe;

use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverElement;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Tagcade\Service\Fetcher\Fetchers\SpringServe\Page\DeliveryReportingPage;
use Tagcade\Service\Fetcher\Fetchers\SpringServe\Page\HomePage;
use Tagcade\Service\Fetcher\Params\SpringServe\SpringServePartnerParamInterface;
use Tagcade\Service\Fetcher\PartnerFetcherAbstract;
use Tagcade\Service\Fetcher\Params\PartnerParamInterface;
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
        if (!$params instanceof SpringServePartnerParamInterface) {
            $this->logger->error('expected SpringServePartnerParam');
            return;
        }

        if (empty($params->getAccount()) || $params->getAccount() == '//i') {
            $this->logger->error('Account regex can not be empty');
            return;
        }

        // Step 1: login
        $this->logger->info('enter login page');
        $homePage = new HomePage($driver, $this->logger);

        $login = $homePage->doLogin($params->getUsername(), $params->getPassword());

        if (!$login) {
            $this->logger->warning('Login system failed');
            return;
        }

        $this->logger->info('end logging in');

        $this->logger->debug('enter download report page');
        $deliveryReportPage = new DeliveryReportingPage($driver, $this->logger);

        $deliveryReportPage->setDownloadFileHelper($this->getDownloadFileHelper());
        $deliveryReportPage->setConfig($params->getConfig());

        $driver->wait()->until(
            WebDriverExpectedCondition::titleContains('SpringServe')
        );

        $accountPositions = $this->getAccountPositionsByFilterRegex($params, $driver);

        foreach ($accountPositions as $pos) {
            /**
             * @var WebDriverElement $userAccountChosen
             */
            $userAccountChosen = $driver->findElement(WebDriverBy::id('user_account_id_chosen'));
            $userAccountChosen->click();

            /**
             * @var WebDriverElement[] $liElements
             */
            $liElements = $userAccountChosen->findElements(WebDriverBy::tagName('li'));

            $needElement = $liElements[$pos];
            $needElement->click();

            $driver->navigate()->to(DeliveryReportingPage::URL);

            $driver->wait()->until(
                WebDriverExpectedCondition::titleContains('SpringServe Reports')
            );

            $this->logger->info('Start downloading reports');
            $deliveryReportPage->getAllTagReports($params->getStartDate(), $params->getEndDate());
            $this->logger->info('Finish downloading reports');
        }

        $this->logoutSystem($driver);
    }

    private function logoutSystem(RemoteWebDriver $driver)
    {
        /**
         * @var WebDriverElement $userAccountChosen
         */
        $userAccountChosen = $driver->findElement(WebDriverBy::id('user_account_id_chosen'));
        $userAccountChosen->click();

        $logOutChosen = $driver->findElement(WebDriverBy::id('navigation-toggle'));
        $logOutChosen->click();
        /**
         * @var WebDriverElement[] $liElements
         */
        $liElements = $logOutChosen->findElements(WebDriverBy::tagName('li'));

        foreach ($liElements as $liElement) {
            if ($liElement->getText() == 'Sign out') {
                $liElement->click();
                break;
            }
        }
    }

    private function getAccountPositionsByFilterRegex(SpringServePartnerParamInterface $params, RemoteWebDriver $driver)
    {
        /**
         * @var WebDriverElement $userAccountChosen
         */
        $userAccountChosen = $driver->findElement(WebDriverBy::id('user_account_id_chosen'));
        $userAccountChosen->click();

        /**
         * @var WebDriverElement[] $liElements
         */
        $liElements = $userAccountChosen->findElements(WebDriverBy::tagName('li'));

        $needElements = [];
        foreach ($liElements as $key => $liElement) {
            if (preg_match($params->getAccount(), $liElement->getText())) {
                $needElements[] = $key;
            }
        }

        $userAccountChosen->click();
        return $needElements;
    }
}