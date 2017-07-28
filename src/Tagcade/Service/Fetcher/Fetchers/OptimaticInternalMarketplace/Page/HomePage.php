<?php

namespace Tagcade\Service\Fetcher\Fetchers\OptimaticInternalMarketplace\Page;

use Exception;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Facebook\WebDriver\WebDriverWait;
use Tagcade\Service\Fetcher\Pages\AbstractHomePage;

class HomePage extends AbstractHomePage
{
    const URL = 'https://publishers.optimatic.com/Portal2/default.aspx';
    const LOG_OUT_URL = 'https://publishers.optimatic.com/Portal2/Logout.aspx';

    public function doLogin($username, $password)
    {
        $this->driver->manage()->timeouts()->pageLoadTimeout(30);
        $this->driver->navigate()->to(self::URL);

        if ($this->isLoggedIn()) {
            return true;
        }
        if (!$this->isCurrentUrl()) {
            $this->navigate();
        }
        $this->logger->debug('Filling credentials ...');
        $this->driver->wait()->until(WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::id('txtUserName')));
        $this->driver->wait()->until(WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::id('txtPassword')));

        $this->driver
            ->findElement(WebDriverBy::id('txtUserName'))
            ->clear()
            ->sendKeys($username);

        $this->driver
            ->findElement(WebDriverBy::id('txtPassword'))
            ->clear()
            ->sendKeys($password);

        $this->logger->debug('Click login button');
        $this->driver->findElement(WebDriverBy::id('signInButton1'))->click();
        sleep(2);
        $this->driver->manage()->timeouts()->pageLoadTimeout(20);
        $waitDriver = new WebDriverWait($this->driver, 20);

        try {
            $waitDriver->until(WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::cssSelector('#menuContent > div.menu > div > div.dashboard')));

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public function isLoggedIn()
    {
        $welcomUser = $this->driver->findElements(WebDriverBy::cssSelector('#menuContent > div.username > a'));

        return empty($welcomUser) ? false : true;
    }

    public function doLogout()
    {
        $this->driver->navigate()->to(self::LOG_OUT_URL);
    }
}