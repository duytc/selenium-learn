<?php

namespace Tagcade\DataSource\PulsePoint\Page;

use Facebook\WebDriver\Exception\NoSuchElementException;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;

class LoginPage extends AbstractPage
{
    const URL = 'https://exchange.pulsepoint.com/AccountMgmt/Login.aspx';

    /**
     * @param string $username
     * @param string $password
     * @return $this
     */
    public function login($username, $password)
    {
        $this->navigateToPartnerDomain();

        if ($this->isLoggedIn()) {
            return $this;
        }

        if (!$this->isCurrentUrl()) {
            $this->navigate();
            $this->driver->wait()->until(WebDriverExpectedCondition::elementToBeClickable(WebDriverBy::id('LoginButton')));
        }

        $this->logger->debug('filling credentials');
        $this->driver
            ->findElement(WebDriverBy::id('UserName'))
            ->clear()
            ->sendKeys($username)
        ;

        $this->driver
            ->findElement(WebDriverBy::id('Password'))
            ->clear()
            ->sendKeys($password)
        ;

        $this->logger->debug('click login button');
        $this->driver->findElement(WebDriverBy::id('LoginButton'))->click();

        $this->driver->wait()->until(
            WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::id('menubar')),
            'Login Fail'
        );
    }

    public function isLoggedIn()
    {
        try {
            $this->driver
                ->findElement(WebDriverBy::cssSelector('.userName'))
            ;

            return true;
        }
        catch (NoSuchElementException $ne) {

        }

        return false;
    }
}