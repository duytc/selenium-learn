<?php

namespace Tagcade\Service\Fetcher\Fetchers\Cedato\Page;

use Exception;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Facebook\WebDriver\WebDriverWait;
use Tagcade\Service\Fetcher\Pages\AbstractHomePage;

class HomePage extends AbstractHomePage
{
    const URL = 'https://publisher.cedato.com/#!/login';
    const LOG_OUT_URL = 'https://dashboard.cedato.com/#/login';

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
        $this->driver->wait()->until(WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::id('username')));
        $this->driver->wait()->until(WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::id('password')));

        $this->driver->manage()->timeouts()->pageLoadTimeout(60);
        $waitDriver = new WebDriverWait($this->driver, 30);
        $retry = 0;
        $isDone = false;
        do {
            try {
                $retry++;
                $this->driver
                    ->findElement(WebDriverBy::id('username'))
                    ->clear()
                    ->sendKeys($username);

                $this->driver
                    ->findElement(WebDriverBy::id('password'))
                    ->clear()
                    ->sendKeys($password);

                $this->logger->debug('click login button');
                $this->driver->findElement(WebDriverBy::id('loginBtn'))->click();
                sleep(2);
                $waitDriver->until(
                    WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::id('navbar-collapse-1'))
                );

                $isDone = true;
            } catch (\Exception $e) {
                if ($retry > 2) {
                    return false;
                }
            }
        } while (!$isDone);

        if ($isDone) {
            return true;
        } else {
            return false;
        }
    }

    public function isLoggedIn()
    {
        $headerMainmenus = $this->driver->findElements(WebDriverBy::id('usernameInNav'));

        return empty($headerMainmenus) ? false : true;
    }

    public function doLogout()
    {
        $this->driver->navigate()->to(self::LOG_OUT_URL);
    }
}