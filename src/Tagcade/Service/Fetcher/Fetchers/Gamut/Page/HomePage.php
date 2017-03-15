<?php

namespace Tagcade\Service\Fetcher\Fetchers\Gamut\Page;

use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Facebook\WebDriver\WebDriverWait;
use Tagcade\Service\Fetcher\Fetchers\PulsePoint\Page\AbstractPage;

class HomePage extends AbstractPage 
{
    const URL = 'http://app-1.gamut.media/MemberPages/Site/default.aspx';

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
        $this->driver->wait()->until(WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::id('UserName')));
        $this->driver->wait()->until(WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::id('Password')));

        $this->driver
            ->findElement(WebDriverBy::id('UserName'))
            ->clear()
            ->sendKeys($username)
        ;

        $this->driver
            ->findElement(WebDriverBy::id('Password'))
            ->clear()
            ->sendKeys($password)
        ;

        $this->logger->debug('click login button');
        $this->driver->findElement(WebDriverBy::id('ctl00_ctl00_BodyContent_BodyContent_SignInButton'))->click();
        sleep(2);
        $this->driver->manage()->timeouts()->pageLoadTimeout(60);
        $waitDriver = new WebDriverWait($this->driver, 60);
        $waitDriver->until(WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::id('ctl00_ctl00_appHeader_signOutLinkButton')));
        return $this->isLoggedIn();
    }

    protected function isLoggedIn()
    {
       $headerMainmenus = $this->driver->findElements(WebDriverBy::id('ctl00_ctl00_appHeader_signOutLinkButton'));

        return empty($headerMainmenus)? false:true;
    }
} 