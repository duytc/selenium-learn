<?php

namespace Tagcade\Service\Fetcher\Fetchers\VertaInternal\Page;

use Exception;
use Facebook\WebDriver\Exception\NoSuchElementException;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Facebook\WebDriver\WebDriverWait;
use Tagcade\Service\Fetcher\Pages\AbstractHomePage;

class HomePage extends AbstractHomePage
{
    const URL = 'https://ssp.vertamedia.com/';

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

        $this->driver->wait(30)->until(WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::name('email')));
        $this->driver
            ->findElement(WebDriverBy::name('email'))
            ->clear()
            ->sendKeys($username);

        $nextBtn = $this->filterElementByTagNameAndText('span', 'NEXT');
        $nextBtn->click();

        $this->driver->wait(30)->until(WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::name('password')));
        $this->driver
            ->findElement(WebDriverBy::name('password'))
            ->clear()
            ->sendKeys($password);

        $this->logger->debug('click login button');
        $signIn = $this->filterElementByTagNameAndText('a', 'SIGN IN');
        $signIn->click();

        $this->driver->manage()->timeouts()->pageLoadTimeout(60);
        $waitDriver = new WebDriverWait($this->driver, 60);

        try {
            $waitDriver->until(WebDriverExpectedCondition::invisibilityOfElementLocated(WebDriverBy::cssSelector('#panel-1026-innerCt')));

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * @inheritdoc
     */
    public function isLoggedIn()
    {
        try {
            $this->driver
                ->findElement(WebDriverBy::cssSelector('dashboard-icon'));

            return true;
        } catch (NoSuchElementException $ne) {
        }

        return false;
    }

    public function doLogout()
    {
        try {
            $this->driver
                ->findElement(WebDriverBy::id('button-1018-btnInnerEl'))->click();

            $logout = $this->filterElementByTagNameAndText('div', 'Logout');
            if ($logout) {
                $logout->click();
            }
            return true;
        } catch (NoSuchElementException $ne) {
        }

        return false;
    }
}