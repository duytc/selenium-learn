<?php

namespace Tagcade\Service\Fetcher\Fetchers\Streamrail\Page;

use Exception;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Facebook\WebDriver\WebDriverWait;
use Tagcade\Service\Fetcher\Pages\AbstractHomePage;

class HomePage extends AbstractHomePage
{
    const URL = 'http://partners.streamrail.com/';

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

        $this->logger->debug('filling credentials');
        $this->driver->wait()->until(WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::id('ember730-input')));
        $this->driver->wait()->until(WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::id('ember751-input')));

        $this->driver
            ->findElement(WebDriverBy::id('ember730-input'))
            ->clear()
            ->sendKeys($username);

        $this->driver
            ->findElement(WebDriverBy::id('ember751-input'))
            ->clear()
            ->sendKeys($password);

        $this->logger->debug('click login button');
        $this->driver->findElement(WebDriverBy::className('action-btn'))->click();
        sleep(2);
        $this->driver->manage()->timeouts()->pageLoadTimeout(60);
        $waitDriver = new WebDriverWait($this->driver, 20);

        try {
            $waitDriver->until(WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::id('ember1172')));

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public function isLoggedIn()
    {
        $headerMainmenus = $this->driver->findElements(WebDriverBy::id('ember1172'));

        return empty($headerMainmenus) ? false : true;
    }
}