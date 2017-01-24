<?php

namespace Tagcade\DataSource\Sovrn\Page;

use Facebook\WebDriver\Exception\NoSuchElementException;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Tagcade\DataSource\PulsePoint\Page\AbstractPage;

class HomePage extends AbstractPage
{
    const URL = 'https://meridian.sovrn.com/#welcome';

    public function doLogin($username, $password)
    {

        $this->driver->manage()->timeouts()->pageLoadTimeout(200);
        $this->driver->manage()->timeouts()->setScriptTimeout(200);

        $this->navigateToPartnerDomain();

        if ($this->isLoggedIn()) {
            return true;
        }

        if (!$this->isCurrentUrl()) {
            $this->navigate();
        }

        $this->logger->debug('filling credentials');
        $this->driver->wait()->until(WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::id('login_username')));
        $this->driver->wait()->until(WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::id('login_password')));

        $this->driver
            ->findElement(WebDriverBy::id('login_username'))
            ->clear()
            ->sendKeys($username)
        ;

        $this->driver
            ->findElement(WebDriverBy::id('login_password'))
            ->clear()
            ->sendKeys($password)
        ;

        $this->logger->debug('click login button');
        $this->driver->findElement(WebDriverBy::id('landing-login'))->click();
        sleep(2);
        $this->driver->wait()->until(WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::id('user-menu-trigger')));
        return $this->isLoggedIn();
    }

    protected function isLoggedIn()
    {
        $logoutElements = $this->driver->findElements(WebDriverBy::id('user-menu-trigger'));

        return empty($logoutElements)? false:true;
    }
} 