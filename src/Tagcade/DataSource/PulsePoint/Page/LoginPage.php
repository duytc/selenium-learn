<?php

namespace Tagcade\DataSource\PulsePoint\Page;

use Facebook\WebDriver\WebDriverBy;

class loginPage extends AbstractPage
{
    const URL = 'https://exchange.pulsepoint.com/AccountMgmt/Login.aspx';

    /**
     * @param string $username
     * @param string $password
     * @return $this
     */
    public function login($username, $password)
    {
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

        $this->driver->findElement(WebDriverBy::id('LoginButton'))->click();
        $this->driver->findElement(WebDriverBy::cssSelector('.tab.manager'))->click();

        return $this;
    }
}