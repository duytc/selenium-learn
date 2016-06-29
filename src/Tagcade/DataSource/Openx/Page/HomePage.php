<?php

namespace Tagcade\DataSource\Openx\Page;

use Facebook\WebDriver\Exception\NoSuchElementException;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Tagcade\DataSource\PulsePoint\Page\AbstractPage;

class HomePage extends AbstractPage
{
    const URL = 'http://us-market.openx.com/#/reports?tab=my_reports';

    public function doLogin($username, $password)
    {
        $this->navigateToPartnerDomain();

        if ($this->isLoggedIn()) {
            return;
        }

        if (!$this->isCurrentUrl()) {
            $this->navigate();
        }
        $this->logger->debug('filling credentials');
        $this->driver->wait()->until(WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::id('email')));
        $this->driver->wait()->until(WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::id('password')));

        $this->driver
            ->findElement(WebDriverBy::id('email'))
            ->clear()
            ->sendKeys($username)
        ;

        $this->driver
            ->findElement(WebDriverBy::id('password'))
            ->clear()
            ->sendKeys($password)
        ;

        $this->logger->debug('click login button');
        $this->driver->findElement(WebDriverBy::id('submit'))->click();
    }

    protected function isLoggedIn()
    {
        try {
            $this->driver
                ->findElement(WebDriverBy::id('header-container'))
            ;

            return true;
        }
        catch (NoSuchElementException $ne) {

        }

        return false;
    }
} 