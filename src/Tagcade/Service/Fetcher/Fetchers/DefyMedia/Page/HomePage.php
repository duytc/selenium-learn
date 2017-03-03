<?php

namespace Tagcade\Service\Fetcher\Fetchers\DefyMedia\Page;

use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Tagcade\Service\Fetcher\Fetchers\PulsePoint\Page\AbstractPage;

class HomePage extends AbstractPage
{
    const URL = 'https://pubportal.defymedia.com/auth/login';

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
        $this->driver->wait()->until(WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::name('identity')));
        $this->driver->wait()->until(WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::name('credential')));

        $this->driver
            ->findElement(WebDriverBy::name('identity'))
            ->clear()
            ->sendKeys($username)
        ;

        $this->driver
            ->findElement(WebDriverBy::name('credential'))
            ->clear()
            ->sendKeys($password)
        ;

        $this->logger->debug('click login button');
        $this->driver->findElement(WebDriverBy::cssSelector('.btn-orange'))->click();

        sleep(2);
        return $this->isLoggedIn();

    }

    protected function isLoggedIn()
    {
        $userNameElements = $this->driver->findElements(WebDriverBy::cssSelector('.username'));

        return empty($userNameElements) ? false:true;

    }
} 