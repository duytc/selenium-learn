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
        $this->navigateToPartnerDomain();

        $this->logger->info('Start login');
        if ($this->isLoggedIn()) {
            return true;
        }

        $this->logger->debug('Try to login');

        if (!$this->isCurrentUrl()) {
            $this->logger->debug('Navigate to login page ');
            $this->navigate();
        }

        $this->driver->findElement(WebDriverBy::cssSelector('a[href="#"]'))->click();

        $this->logger->debug('Filling username and password');

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

        $this->logger->debug('Click login button');
        $this->driver->findElement(WebDriverBy::name('login'))->click();
        sleep(2);

        return $this->isLoggedIn();
    }

    /**
     * @return bool
     */
    protected function isLoggedIn()
    {
        $reportings = $this->driver->findElements(WebDriverBy::cssSelector('div[class="logout"]'));

        return empty($reportings) ? false : true;
    }
} 