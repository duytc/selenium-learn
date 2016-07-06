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
            return true;
        }

        if (!$this->isCurrentUrl()) {
            $this->navigate();
        }
        $this->logger->debug('filling credentials');
        $this->driver->wait()->until(WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::id('email')));
        $this->driver->wait()->until(WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::id('password')));
        $this->driver->wait()->until(WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::id('submit')));

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

        $this->driver->manage()->timeouts()->setScriptTimeout(200);
        $this->driver->manage()->timeouts()->pageLoadTimeout(200);
        $this->logger->debug('click login button');
        $this->driver->findElement(WebDriverBy::id('submit'))->click();

        $errors = $this->driver->findElements(WebDriverBy::cssSelector('div[class="error"]'));

        return count($errors) >0 ? false: true;
    }

    protected function isLoggedIn()
    {
        $reportings = $this->driver->findElements(WebDriverBy::id('reporting'));

        return empty($reportings)? false:true;
    }
} 