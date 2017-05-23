<?php

namespace Tagcade\Service\Fetcher\Fetchers\YellowHammer\Page;

use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Tagcade\Service\Fetcher\Pages\AbstractHomePage;

class HomePage extends AbstractHomePage
{
    const URL = 'http://publishers.yhmg.com/signin';

    public function doLogin($username, $password)
    {
        $this->navigateToPartnerDomain();

        if ($this->isLoggedIn()) {
            return true;
        }

        if (!$this->isCurrentUrl()) {
            $this->navigate();
        }

        $this->logger->debug('filling credentials');
        $this->driver->wait()->until(WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::id('user_email')));

        $this->driver
            ->findElement(WebDriverBy::id('user_email'))
            ->clear()
            ->sendKeys($username);

        $this->driver
            ->findElement(WebDriverBy::id('user_password'))
            ->clear()
            ->sendKeys($password);

        $this->logger->debug('click login button');
        $this->driver->findElement(WebDriverBy::name('commit'))->click();

        sleep(2);
        return $this->isLoggedIn();
    }

    public function isLoggedIn()
    {
        $dashboardElements = $this->driver->findElements(WebDriverBy::id('dashboard'));
        return empty($dashboardElements) ? false : true;

    }

    public function doLogout()
    {
        $logoutAreaCss = '#username > ul > li > a';
        $this->driver->findElement(WebDriverBy::cssSelector($logoutAreaCss))->click();

        $logoutButtonCss = '#username > ul > li > ul > li:nth-child(9) > a';
        $this->driver->findElement(WebDriverBy::cssSelector($logoutButtonCss))->click();
    }
}