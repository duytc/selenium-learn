<?php

namespace Tagcade\DataSource\YellowHammer\Page;

use Facebook\WebDriver\Exception\NoSuchElementException;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Tagcade\DataSource\PulsePoint\Page\AbstractPage;

class HomePage extends AbstractPage
{
    const URL = 'http://publishers.yhmg.com/signin';


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
        $this->driver->wait()->until(WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::id('user_email')));

        $this->driver
            ->findElement(WebDriverBy::id('user_email'))
            ->clear()
            ->sendKeys($username)
        ;

        $this->driver
            ->findElement(WebDriverBy::id('user_password'))
            ->clear()
            ->sendKeys($password)
        ;

        $this->info('click login button');
        $this->driver->findElement(WebDriverBy::name('commit'))->click();

        usleep(100);

        $this->driver->wait()->until(WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::cssSelector('#username .icon-user')));
    }

    protected function isLoggedIn()
    {
        try {
            $this->driver
                ->findElement(WebDriverBy::id('dashboard'))
            ;

           return true;
        }
        catch (NoSuchElementException $ne) {

        }

        return false;
    }
}