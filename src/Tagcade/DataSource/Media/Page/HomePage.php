<?php

namespace Tagcade\DataSource\Media\Page;

use Facebook\WebDriver\Exception\NoSuchElementException;
use Facebook\WebDriver\Remote\RemoteWebElement;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Tagcade\DataSource\PulsePoint\Page\AbstractPage;

class HomePage extends AbstractPage
{
    const URL = 'http://www.media.net';
    const CONTROL_PANEL_LINK = 'control.media.net//home';

    /**
     * @param $username
     * @param $password
     * @throws NoSuchElementException
     * @throws \Exception
     * @throws \Facebook\WebDriver\Exception\TimeOutException
     * @throws null
     * @return bool
     */

    public function doLogin($username, $password)
    {
        $this->navigateToPartnerDomain();

        if ($this->isLoggedIn()) {
            return true;
        }

        if (!$this->isCurrentUrl()) {
            $this->navigate();
        }

        $this->info('Filling username and password');

        $this->driver->wait()->until(WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::id('signIn2')));
        $this->driver->findElement(WebDriverBy::id('signIn2'))->click();
        $this->driver->wait()->until(WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::id('userloginform')));

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

        $this->info('Click login button');
        $this->driver->findElement(WebDriverBy::id('publisherSignin'))->click();
        try {
            $this->driver->wait()->until(WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::id('dashboard')));
            return true;
        }catch (NoSuchElementException $e){
            $this->info('Username or password is not correct!');
            return false;
        }
    }

    /**
     * @return bool
     */
    protected function isLoggedIn()
    {
        if (true == $this->findAndClickLinkByHref(self::CONTROL_PANEL_LINK)) {
            return true;
        }

        /** @var RemoteWebElement[] $dashboardElement  */
        $dashboardElement = $this->driver->findElements(WebDriverBy::id('dashboard'));
        if (count($dashboardElement) > 0) {
            $this->driver->findElement(WebDriverBy::id('dashboard'))->click();
            return true;
        }
        return false;
    }

    public function findAndClickLinkByHref($findString) {

        /** @var RemoteWebElement[] $aElements  */
        $aElements = $this->driver->findElements(WebDriverBy::tagName('a'));

        foreach ($aElements as $aElement) {
            $herfValue = $aElement->getAttribute('href');
            if (strpos($herfValue, $findString) !== false) {
               $this->driver->navigate()->to($herfValue);
                return true;
            }
        }
        return false;
    }
} 