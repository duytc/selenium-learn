<?php

namespace Tagcade\Service\Fetcher\Fetchers\PubVentures\Page;

use Facebook\WebDriver\Exception\NoSuchElementException;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Tagcade\Service\Fetcher\Pages\AbstractHomePage;

class HomePage extends AbstractHomePage
{
    const URL = 'http://ui.pubventuresmedia.com/?redir=%2Freport%2Fpublisher%2F%3Fid%3D384105';

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
        $this->driver->wait()->until(WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::id('anxs-login-username')));
        $this->driver->wait()->until(WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::id('anxs-login-password')));

        $this->driver
            ->findElement(WebDriverBy::id('anxs-login-username'))
            ->clear()
            ->sendKeys($username);

        $this->driver
            ->findElement(WebDriverBy::id('anxs-login-password'))
            ->clear()
            ->sendKeys($password);

        $this->logger->debug('click login button');
        $this->driver->findElement(WebDriverBy::cssSelector('.btn'))->click();

        $error = $this->driver->findElements(WebDriverBy::cssSelector('#login-form > fieldset > div.alert.error'));

        if (count($error) > 0) {
            return false;
        }

        return true;
    }

    public function isLoggedIn()
    {
        try {
            $this->driver->findElement(WebDriverBy::cssSelector('lucid-Button-content'));

            return true;
        } catch (NoSuchElementException $ne) {
        }

        return false;
    }
}