<?php

namespace Tagcade\Service\Fetcher\Fetchers\SpringServe\Page;

use Exception;
use Facebook\WebDriver\Exception\NoSuchElementException;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Facebook\WebDriver\WebDriverWait;
use Tagcade\Service\Fetcher\Pages\AbstractHomePage;

class HomePage extends AbstractHomePage
{
    const URL = 'http://video.springserve.com/users/sign_in';

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
        $this->driver->wait(30)->until(WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::name('user[email]')));
        $this->driver->wait()->until(WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::name('user[password]')));

        $this->driver
            ->findElement(WebDriverBy::name('user[email]'))
            ->clear()
            ->sendKeys($username);

        $this->driver
            ->findElement(WebDriverBy::name('user[password]'))
            ->clear()
            ->sendKeys($password);

        $this->logger->debug('click login button');
        $this->driver->findElement(WebDriverBy::name('commit'))->click();
        $this->driver->manage()->timeouts()->pageLoadTimeout(60);
        $waitDriver = new WebDriverWait($this->driver, 60);

        try {
            $waitDriver->until(WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::cssSelector('body > main > div > div.header-modern > h1 > span:nth-child(1)')));

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * @inheritdoc
     */
    public function isLoggedIn()
    {
        try {
            $this->driver
                ->findElement(WebDriverBy::cssSelector('dashboard-icon'));

            return true;
        } catch (NoSuchElementException $ne) {
        }

        return false;
    }
}