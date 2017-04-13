<?php


namespace Tagcade\Service\Fetcher\Fetchers\Ads4Games\Page;


use Facebook\WebDriver\Exception\NoSuchElementException;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Tagcade\Service\Fetcher\Pages\AbstractHomePage;

class HomePage extends AbstractHomePage
{
    const URL = 'https://traffic.a4g.com/www/admin/index.php';

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

        if ($this->isLoggedIn()) {
            return true;
        }

        if (!$this->isCurrentUrl()) {
            $this->navigate();
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
        $this->driver->findElement(WebDriverBy::id('login'))->click();

        return $this->isLoggedIn();
    }

    /**
     * @return bool
     */
    public function isLoggedIn()
    {
        $logoutElements = $this->driver->findElements(WebDriverBy::cssSelector('li[class="buttonLogout"]'));

        return empty($logoutElements) ? false : true;
    }
}