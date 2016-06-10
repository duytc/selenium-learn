<?php


namespace Tagcade\DataSource\NativeAds\Page;


use Facebook\WebDriver\Exception\NoSuchElementException;
use Facebook\WebDriver\Exception\TimeOutException;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Tagcade\DataSource\PulsePoint\Page\AbstractPage;

class HomePage extends AbstractPage {

    const URL = 'https://nativeads.com/publisher/login.php';

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

        $this->driver->wait()->until(WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::name('username')));

        $this->driver
            ->findElement(WebDriverBy::name('username'))
            ->clear()
            ->sendKeys($username)
        ;

        $this->driver
            ->findElement(WebDriverBy::name('password'))
            ->clear()
            ->sendKeys($password)
        ;

        $this->info('Click login button');
        $this->driver->findElement(WebDriverBy::cssSelector('button[class="btn btn-primary"]'))->click();

        try {
            $this->driver->wait()->until(WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::cssSelector('a[href="logout.php"]')));
            return true;
        }catch (NoSuchElementException $e){
            $this->info('Username or password is not correct!');
            return false;
        } catch (TimeOutException $e) {
            $this->info('Time out exception');
            return false;
        }

    }

    /**
     * @return bool
     */
    protected function isLoggedIn()
    {
        try {
            $this->driver->findElement(WebDriverBy::cssSelector('a[href="logout.php"]'));
            return true;

        } catch (NoSuchElementException $e) {

            return false;
        }
    }

} 