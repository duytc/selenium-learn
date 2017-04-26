<?php

namespace Tagcade\Service\Fetcher\Fetchers\SpringServe;

use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverElement;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Psr\Log\LoggerInterface;
use Tagcade\Service\Fetcher\Fetchers\SpringServe\Page\DeliveryReportingPage;
use Tagcade\Service\Fetcher\Fetchers\SpringServe\Page\HomePage;
use Tagcade\Service\Fetcher\Params\PartnerParamInterface;
use Tagcade\Service\Fetcher\Params\SpringServe\SpringServePartnerParamInterface;
use Tagcade\Service\Fetcher\PartnerFetcherAbstract;

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
            $deliveryReportPage->getAllTagReports($params);
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

        $logOutChosen = $driver->findElement(WebDriverBy::cssSelector('#navbar-fixed-top > div > div.collapse.navbar-collapse > ul > li:nth-child(4)'));
        $logOutChosen->click();
        /**
         * @var WebDriverElement[] $liElements
         */
        $liElements = $logOutChosen->findElements(WebDriverBy::tagName('li'));

        foreach ($liElements as $liElement) {
            if ($liElement->getText() == 'SIGN OUT') {
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

    /**
     * @inheritdoc
     */
    public function getHomePage(RemoteWebDriver $driver, LoggerInterface $logger)
    {
        return new HomePage($driver, $this->logger);
    }
}