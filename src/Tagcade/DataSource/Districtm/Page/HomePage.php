<?php

namespace Tagcade\DataSource\Districtm\Page;

use Facebook\WebDriver\Exception\NoSuchElementException;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Tagcade\DataSource\PulsePoint\Page\AbstractPage;

class HomePage extends AbstractPage 
{
    const URL = 'http://b3.districtm.ca';
    
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
        $this->driver->wait()->until(WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::id('username')));
        $this->driver->wait()->until(WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::id('password')));

        $this->driver
            ->findElement(WebDriverBy::id('username'))
            ->clear()
            ->sendKeys($username)
        ;

        $this->driver
            ->findElement(WebDriverBy::id('password'))
            ->clear()
            ->sendKeys($password)
        ;

        $this->logger->debug('click login button');
        $this->driver->findElement(WebDriverBy::cssSelector('.btn'))->click();

        $error = $this->driver->findElements(WebDriverBy::cssSelector('#form_container > div > div:nth-child(4) > form > div:nth-child(3) > div.col-md-8 > p.text-danger'));
        if(count($error) > 0) {
            return false;
        }

        return true;
    }

    protected function isLoggedIn()
    {
        try {
            $this->driver
                ->findElement(WebDriverBy::id('sub-nav'))
            ;

            return true;
        }
        catch (NoSuchElementException $ne) {

        }

        return false;
    }
} 