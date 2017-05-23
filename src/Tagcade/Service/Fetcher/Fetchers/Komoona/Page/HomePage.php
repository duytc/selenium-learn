<?php

namespace Tagcade\Service\Fetcher\Fetchers\Komoona\Page;

use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Tagcade\Service\Fetcher\Pages\AbstractHomePage;

class HomePage extends AbstractHomePage
{
    const URL = 'http://www.komoona.com/#home';

    public function doLogin($username, $password)
    {
        sleep(2);

        $this->navigateToPartnerDomain();

        if ($this->isLoggedIn()) { // current page is another page that tell the user is already logged in
            return true;
        }

        if (!$this->isCurrentUrl()) { // redirect to current page if user is not logged in yet
            $this->navigate();
        }

        $this->driver->wait()->until(WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::id('login')));

        $this->logger->debug('filling credentials');
        $this->driver
            ->findElement(WebDriverBy::id('login'))
            ->click();

        $this->driver
            ->findElement(WebDriverBy::id('username'))
            ->clear()
            ->sendKeys($username);

        $this->driver
            ->findElement(WebDriverBy::id('password'))
            ->clear()
            ->sendKeys($password);

        $this->driver->findElement(WebDriverBy::id('login-submit'))->click();

        sleep(2);

        return $this->isLoggedIn();
    }

    public function isLoggedIn()
    {
        $logoutElements = $this->driver->findElements(WebDriverBy::cssSelector('input[value=logout]'));

        return empty($logoutElements) ? false : true;
    }

    public function doLogout()
    {
        $logOutCss = '#submit > input[type="submit"]';
        $this->driver->findElement(WebDriverBy::cssSelector($logOutCss))->click();
    }
}