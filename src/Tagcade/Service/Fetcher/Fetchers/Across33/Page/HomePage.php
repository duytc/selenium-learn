<?php

namespace Tagcade\Service\Fetcher\Fetchers\Across33\Page;

use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Facebook\WebDriver\WebDriverWait;
use Tagcade\Service\Fetcher\Pages\AbstractHomePage;

class HomePage extends AbstractHomePage
{
    const URL = 'https://platform.33across.com/sessions/new';

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
        $this->driver->wait()->until(WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::id('login')));
        $this->driver->wait()->until(WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::id('password')));

        $this->driver
            ->findElement(WebDriverBy::id('login'))
            ->clear()
            ->sendKeys($username);

        $this->driver
            ->findElement(WebDriverBy::id('password'))
            ->clear()
            ->sendKeys($password);

        $this->logger->debug('click login button');
        $this->driver->findElement(WebDriverBy::cssSelector('.btn'))->click();
        sleep(2);
        $this->driver->manage()->timeouts()->pageLoadTimeout(60);
        $waitDriver = new WebDriverWait($this->driver, 60);
        $waitDriver->until(WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::cssSelector('a[href="/account/log_out"]')));
        return $this->isLoggedIn();
    }

    public function isLoggedIn()
    {
        $headerMainmenus = $this->driver->findElements(WebDriverBy::cssSelector('a[href="/account/log_out"]'));

        return empty($headerMainmenus) ? false : true;
    }

    public function doLogout()
    {
        $logOutButtonCss = '#main-nav > ul > li.logout > a';
        $this->driver->findElement(WebDriverBy::cssSelector($logOutButtonCss))->click();
    }
}