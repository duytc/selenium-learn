<?php


namespace Tagcade\DataSource\Ads4Games\Page;


use Facebook\WebDriver\Exception\NoSuchElementException;
use Facebook\WebDriver\Remote\RemoteWebElement;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Tagcade\DataSource\PulsePoint\Page\AbstractPage;

class HomePage extends AbstractPage {

    const URL = 'https://traffic.a4g.com/www/admin/index.php';
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

        $this->driver->wait()->until(WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::id('username')));

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

        $this->info('Click login button');
        $this->driver->findElement(WebDriverBy::id('login'))->click();
        try {
            $this->driver->wait()->until(WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::cssSelector('li[class="buttonLogout"]')));
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
        try {
            $this->driver->findElement(WebDriverBy::cssSelector('li[class="buttonLogout"]'));
            return true;

        } catch (NoSuchElementException $e) {

            return false;
        }
    }

} 