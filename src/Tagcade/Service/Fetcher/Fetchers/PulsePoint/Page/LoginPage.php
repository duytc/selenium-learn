<?php

namespace Tagcade\Service\Fetcher\Fetchers\PulsePoint\Page;

use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Tagcade\Service\Fetcher\Pages\AbstractHomePage;

class LoginPage extends AbstractHomePage
{
    const URL = 'https://exchange.pulsepoint.com/AccountMgmt/Login.aspx';

    /**
     * @inheritdoc
     */
    public function doLogin($username, $password)
    {
        $this->navigateToPartnerDomain();

        if ($this->isLoggedIn()) {
            return true;
        }

        if (!$this->isCurrentUrl()) {
            $this->navigate();
            $this->driver->wait()->until(WebDriverExpectedCondition::elementToBeClickable(WebDriverBy::id('LoginButton')));
        }

        $this->logger->debug('filling credentials');
        $this->driver
            ->findElement(WebDriverBy::id('UserName'))
            ->clear()
            ->sendKeys($username);

        $this->driver
            ->findElement(WebDriverBy::id('Password'))
            ->clear()
            ->sendKeys($password);

        $this->driver->manage()->timeouts()->pageLoadTimeout(200);
        $this->driver->manage()->timeouts()->setScriptTimeout(200);
        $this->logger->debug('click login button');
        $this->driver->findElement(WebDriverBy::id('LoginButton'))->click();

        sleep(2);
        return $this->isLoggedIn();
    }

    public function isLoggedIn()
    {
        $logoutCss = 'a[href="/Publisher/logout.aspx"]';
        $userNameElements = $this->driver->findElements(WebDriverBy::cssSelector($logoutCss));

        return empty($userNameElements) ? false : true;
    }
}