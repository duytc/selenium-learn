<?php

namespace Tagcade\DataSource\Komoona\Page;

use Facebook\WebDriver\Exception\NoSuchElementException;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Tagcade\DataSource\PulsePoint\Page\AbstractPage;

class HomePage extends AbstractPage
{
    const URL = 'http://www.komoona.com/#home';


    public function doLogin($username, $password)
    {
        $this->navigateToPartnerDomain();

        if ($this->isLoggedIn()) { // current page is another page that tell the user is already logged in
            return;
        }

        if (!$this->isCurrentUrl()) { // redirect to current page if user is not logged in yet
            $this->navigate();
        }

        $this->driver->wait()->until(WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::id('login')));

        $this->logger->debug('filling credentials');
        $this->driver
            ->findElement(WebDriverBy::id('login'))
            ->click()
        ;

        $this->driver
            ->findElement(WebDriverBy::id('username'))
            ->clear()
            ->sendKeys($username)
        ;

        $this->driver
            ->findElement(WebDriverBy::id('password'))
            ->clear()
            ->sendKeys($password)
        ;

        $this->driver->findElement(WebDriverBy::id('login-submit'))->click();
        $this->driver->wait()->until(WebDriverExpectedCondition::titleContains('Control Panel'), 'Login Fail');

    }

    protected function isLoggedIn()
    {

        try {

            $logoutLink = $this->driver
                ->findElement(WebDriverBy::id('logout'))
            ;

            if (strtolower($logoutLink->getText()) == 'logout') {
                return true;
            }
            return true;
        }
        catch (NoSuchElementException $ne) {

        }

        try {
            $this->driver
                ->findElement(WebDriverBy::cssSelector('input[value=logout]'))
            ;

            return true;
        }
        catch (NoSuchElementException $ne) {

        }

        return false;
    }
}