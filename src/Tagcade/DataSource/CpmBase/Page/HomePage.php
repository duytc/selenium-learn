<?php

namespace Tagcade\DataSource\CpmBase\Page;

use Facebook\WebDriver\Exception\NoSuchElementException;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Tagcade\DataSource\PulsePoint\Page\AbstractPage;

class HomePage extends AbstractPage {

    const URL = 'http://cpmbase.com';

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
        $this->logger->info('Make sure current domain is for CpmBase partner');
        $this->navigateToPartnerDomain();

        $this->logger->info('start login');
        if ($this->isLoggedIn()) {
            return true;
        }

        $this->logger->info('Try to login');

        if (!$this->isCurrentUrl()) {
            $this->logger->info('Navigate to login page ');
            $this->navigate();
        }

        $this->driver->findElement(WebDriverBy::cssSelector('a[href="#"]'))->click();

        $this->info('Filling username and password');

        $this->driver->wait()->until(WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::name('email')));
        $this->driver
            ->findElement(WebDriverBy::name('email'))
            ->clear()
            ->sendKeys($username)
        ;

        $this->driver
            ->findElement(WebDriverBy::name('password'))
            ->clear()
            ->sendKeys($password)
        ;

        $this->info('Click login button');
        $this->driver->findElement(WebDriverBy::name('login'))->click();

        $this->driver->wait()->until(WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::cssSelector('a[href="/reporting"]')));

        return true;
    }

    /**
     * @return bool
     */
    protected function isLoggedIn()
    {
        try {
            $this->driver->findElement(WebDriverBy::cssSelector('div[class="logout"]'));

            $this->logger->info('User is logged in');
            return true;

        } catch (NoSuchElementException $e) {

            return false;
        }
    }
} 