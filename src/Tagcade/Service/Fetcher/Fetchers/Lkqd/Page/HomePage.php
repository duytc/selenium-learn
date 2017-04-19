<?php

namespace Tagcade\Service\Fetcher\Fetchers\Lkqd\Page;


use Exception;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Facebook\WebDriver\WebDriverWait;
use Tagcade\Service\Fetcher\Pages\AbstractHomePage;

class HomePage extends AbstractHomePage
{
    const URL = 'https://ui.lkqd.com/login';

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
        $this->driver->wait()->until(WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::id('username')));
        $this->driver->wait()->until(WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::id('password')));
        $this->driver->wait()->until(WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::tagName('button')));

        $this->driver
            ->findElement(WebDriverBy::id('username'))
            ->clear()
            ->sendKeys($username);

        $this->driver
            ->findElement(WebDriverBy::id('password'))
            ->clear()
            ->sendKeys($password);

        $this->driver->manage()->timeouts()->setScriptTimeout(200);
        $this->driver->manage()->timeouts()->pageLoadTimeout(200);
        $this->logger->debug('click login button');
        $this->driver->findElement(WebDriverBy::tagName('button'))->click();

        $this->driver->manage()->timeouts()->pageLoadTimeout(60);
        $waitDriver = new WebDriverWait($this->driver, 60);

        try {
            $waitDriver->until(WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::id('reports')));

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public function isLoggedIn()
    {
        $reportings = $this->driver->findElements(WebDriverBy::id('reports'));

        return empty($reportings) ? false : true;
    }
}