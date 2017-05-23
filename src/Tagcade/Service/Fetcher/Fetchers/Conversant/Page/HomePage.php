<?php

namespace Tagcade\Service\Fetcher\Fetchers\Conversant\Page;

use Facebook\WebDriver\Exception\NoSuchElementException;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Tagcade\Service\Fetcher\Pages\AbstractHomePage;

class HomePage extends AbstractHomePage
{
    const URL = 'https://admin.valueclickmedia.com/corp/login';
    const LOGOUT = 'https://admin.valueclickmedia.com/corp/login';

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
        $this->driver->wait()->until(WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::id('login-user_name')));
        $this->driver->wait()->until(WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::cssSelector('#login-password-content > div > input')));

        $this->driver
            ->findElement(WebDriverBy::id('login-user_name'))
            ->clear()
            ->sendKeys($username);

        $this->driver
            ->findElement(WebDriverBy::cssSelector('#login-password-content > div > input'))
            ->clear()
            ->sendKeys($password);

        $this->logger->debug('click login button');
        $this->driver->findElement(WebDriverBy::cssSelector('.submit_input'))->click();

        $error = $this->driver->findElements(WebDriverBy::cssSelector('#login-password-errors'));
        if (count($error) > 0) {
            return false;
        }

        return $this->isLoggedIn();
    }

    public function isLoggedIn()
    {
        try {
            $this->driver
                ->findElement(WebDriverBy::id('page_header'));

            return true;
        } catch (NoSuchElementException $ne) {

        }

        return false;
    }

    public function doLogout()
    {
        $this->driver->navigate()->to(static::LOGOUT);

        return $this;
    }
}