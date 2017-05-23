<?php


namespace Tagcade\Service\Fetcher\Fetchers\Adtech\Page;


use Exception;
use Facebook\WebDriver\Exception\NoSuchElementException;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Tagcade\Service\Fetcher\Pages\AbstractHomePage;

class HomePage extends AbstractHomePage
{
    const URL = 'https://marketplace.adtechus.com/h2/index.do';

    /**
     * @param $username
     * @param $password
     * @throws NoSuchElementException
     * @throws Exception
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
            ->sendKeys($username);

        $this->driver
            ->findElement(WebDriverBy::id('password'))
            ->clear()
            ->sendKeys($password);

        $this->logger->debug('Click login button');
        $this->driver->findElement(WebDriverBy::cssSelector('.btn'))->click();

        sleep(2);
        return $this->isLoggedIn();
    }

    /**
     * @return bool
     */
    public function isLoggedIn()
    {
        $logOutButton = '#navLogoutItem';
        $logOutElements = $this->driver->findElements(WebDriverBy::cssSelector($logOutButton));

        return empty($logOutElements) ? false : true;
    }

    public function doLogout()
    {
        $logoutButtonCss = '#navLogoutItem';
        $this->driver->findElement(WebDriverBy::cssSelector($logoutButtonCss))->click();
        $confirmLogoutButtonsCss = '#button_caption\2e yes';
        $this->driver->findElement(WebDriverBy::cssSelector($confirmLogoutButtonsCss))->click();
    }
}