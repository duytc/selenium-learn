<?php


namespace Tagcade\DataSource\Adtech\Page;


use Facebook\WebDriver\Exception\NoSuchElementException;
use Facebook\WebDriver\Remote\RemoteWebElement;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Tagcade\DataSource\PulsePoint\Page\AbstractPage;

class HomePage extends AbstractPage {

    const URL = 'https://marketplace.adtechus.com/h2/index.do';

    /**
     * @param $username
     * @param $password
     * @throws NoSuchElementException
     * @throws \Exception
     * @throws \Facebook\WebDriver\Exception\TimeOutException
     * @throws null
     * @return bool
     */

    public function doLogin($username, $password)
    {
        $this->navigateToPartnerDomain();

        if (!$this->isCurrentUrl()) {
            $this->navigate();
        }

        if ($this->isLoggedIn()) {
            return true;
        }

        $this->logger->debug('Filling username and password');

        $this->driver->wait()->until(WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::id('username')));

        $this->driver
            ->findElement(WebDriverBy::id('username'))
            ->clear()
            ->sendKeys($username)
        ;

        $this->driver
            ->findElement(WebDriverBy::id('password'))
            ->clear()
            ->sendKeys($password)
        ;

        $this->logger->debug('Click login button');
        $this->driver->findElement(WebDriverBy::cssSelector('.btn'))->click();

        try {

            $logOutButton = '#navLogoutItem';
            $this->driver->wait()->until(WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::cssSelector($logOutButton)));

            $accountSummaryCss = '#ssp_stats_loading_ssp_stats_lineChartPanel';
            $this->driver->wait()->until(WebDriverExpectedCondition::invisibilityOfElementLocated(WebDriverBy::cssSelector($accountSummaryCss)));
            return true;
        }catch (NoSuchElementException $e){
            $this->info('Username or password is not correct!');
            return false;
        }
    }

    /**
     * @return bool
     */
    protected function isLoggedIn()
    {
        try {
            $logOutButton = '#marketplaceNavigationbarForthTab > a:nth-child(1)';
            $this->driver->findElement(WebDriverBy::cssSelector($logOutButton));

            return true;

        } catch (NoSuchElementException $e) {

            $this->logger->debug('User does not login!');
            return false;
        }
    }

}