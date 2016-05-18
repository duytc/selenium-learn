<?php

namespace Tagcade\DataSource\Across33\Page;
use Facebook\WebDriver\Exception\NoSuchElementException;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Tagcade\DataSource\PulsePoint\Page\AbstractPage;

class HomePage extends AbstractPage 
{
    const URL = 'https://platform.33across.com/sessions/new';
    
    public function doLogin($username, $password)
    {
        $this->navigateToPartnerDomain();

        if ($this->isLoggedIn()) {
            return;
        }

        if (!$this->isCurrentUrl()) {
            $this->navigate();
        }
        $this->info('filling credentials');
        $this->driver->wait()->until(WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::id('login')));
        $this->driver->wait()->until(WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::id('password')));

        $this->driver
            ->findElement(WebDriverBy::id('login'))
            ->clear()
            ->sendKeys($username)
        ;

        $this->driver
            ->findElement(WebDriverBy::id('password'))
            ->clear()
            ->sendKeys($password)
        ;

        $this->info('click login button');
        $this->driver->findElement(WebDriverBy::cssSelector('.btn'))->click();
//        $this->driver->wait()->until(WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::id('site_rollups')));
    }

    protected function isLoggedIn()
    {
        try {
            $this->driver
                ->findElement(WebDriverBy::id('header-mainmenu'))
            ;

            return true;
        }
        catch (NoSuchElementException $ne) {

        }

        return false;
    }
} 