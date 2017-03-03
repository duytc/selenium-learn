<?php

namespace Tagcade\Service\Fetcher\Fetchers\Technorati\Page;

use Facebook\WebDriver\Exception\NoSuchElementException;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Tagcade\Service\Fetcher\Fetchers\PulsePoint\Page\AbstractPage;

class HomePage extends AbstractPage 
{
    const URL = 'https://mycontango.technorati.com/#/login';
    
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
        $this->driver->wait()->until(WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::cssSelector('input[ng-model="credentials.email"]')));
        $this->driver->wait()->until(WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::cssSelector('input[ng-model="credentials.password"]')));

        $this->driver
            ->findElement(WebDriverBy::cssSelector('input[ng-model="credentials.email"]'))
            ->clear()
            ->sendKeys($username)
        ;

        $this->driver
            ->findElement(WebDriverBy::cssSelector('input[ng-model="credentials.password"]'))
            ->clear()
            ->sendKeys($password)
        ;

        $this->logger->debug('click login button');
        $this->driver->findElement(WebDriverBy::cssSelector('.btn-success'))->click();

        $error = $this->driver->findElements(WebDriverBy::cssSelector('.modal-title'));
        if(count($error) == 0) {
            return false;
        }

        return true;
    }

    protected function isLoggedIn()
    {
        try {
            $this->driver
                ->findElement(WebDriverBy::cssSelector('dashboard-icon'))
            ;

            return true;
        }
        catch (NoSuchElementException $ne) {

        }

        return false;
    }
} 