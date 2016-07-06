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
            return true;
        }

        if (!$this->isCurrentUrl()) {
            $this->navigate();
        }

        $this->logger->debug('filling credentials');
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

        $this->logger->debug('click login button');
        $this->driver->findElement(WebDriverBy::name('commit'))->click();

        sleep(2);
        return $this->isLoggedIn();
    }

    protected function isLoggedIn()
    {
        $dashboardElements = $this->driver->findElements(WebDriverBy::id('dashboard'));
        return empty($dashboardElements) ? false: true;

    }

}