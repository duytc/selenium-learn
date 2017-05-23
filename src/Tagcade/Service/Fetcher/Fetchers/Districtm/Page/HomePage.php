<?php

namespace Tagcade\Service\Fetcher\Fetchers\Districtm\Page;

use Facebook\WebDriver\Exception\NoSuchElementException;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Tagcade\Service\Fetcher\Pages\AbstractHomePage;

class HomePage extends AbstractHomePage
{
    const URL = 'http://b3.districtm.ca';

    public function doLogin($username, $password)
    {
        sleep(2);

        $this->navigateToPartnerDomain();

        if ($this->isLoggedIn()) {
            return;
        }

        if (!$this->isCurrentUrl()) {
            $this->navigate();
        }
        $this->logger->debug('filling credentials');
        $this->driver->wait()->until(WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::id('username')));
        $this->driver->wait()->until(WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::id('password')));

        $this->driver
            ->findElement(WebDriverBy::id('username'))
            ->clear()
            ->sendKeys($username);

        $this->driver
            ->findElement(WebDriverBy::id('password'))
            ->clear()
            ->sendKeys($password);

        $this->logger->debug('click login button');
        $this->driver->findElement(WebDriverBy::cssSelector('.btn'))->click();

        usleep(10);

        $error = $this->driver->findElements(WebDriverBy::cssSelector('#form_container > div > div:nth-child(4) > form > div:nth-child(3) > div.col-md-8 > p.text-danger'));
        if (count($error) > 0) {
            return false;
        }

        return true;
    }

    public function isLoggedIn()
    {
        try {
            $this->driver
                ->findElement(WebDriverBy::id('sub-nav'));

            return true;
        } catch (NoSuchElementException $ne) {

        }

        return false;
    }

    public function doLogout()
    {
        $logoutAreaCss = '#mobile-menu > ul > li.dropdown > a';
        $this->driver->findElement(WebDriverBy::cssSelector($logoutAreaCss))->click();

        $logoutButtonCss = '#mobile-menu > ul > li.dropdown.open > ul > li:nth-child(3) > a';
        $this->driver->findElement(WebDriverBy::cssSelector($logoutButtonCss))->click();
    }
}