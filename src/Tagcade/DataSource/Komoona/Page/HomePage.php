<?php

namespace Tagcade\DataSource\Komoona\Page;

use Facebook\WebDriver\Exception\NoSuchElementException;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Tagcade\DataSource\PulsePoint\Page\AbstractPage;

class HomePage extends AbstractPage
{
    const URL = 'http://www.komoona.com/#home';


    public function doLogin($username, $password)
    {
        try {
            $this->info('filling credentials');
            $this->driver
                ->findElement(WebDriverBy::id('login'))
                ->click()
            ;

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

            $this->driver->findElement(WebDriverBy::id('login-submit'))->click();
            $this->driver->wait()->until(WebDriverExpectedCondition::titleContains('Control Panel'));
        }
        catch (NoSuchElementException $ne) {

        }
    }
}