<?php


namespace Tagcade\DataSource\Epom\Page;


use Facebook\WebDriver\Exception\NoSuchElementException;
use Facebook\WebDriver\Remote\RemoteWebElement;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Tagcade\DataSource\PulsePoint\Page\AbstractPage;

class HomePage extends AbstractPage {

    const URL   =   'http://www.epommarket.com';

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

        $submitButtonLoginCss = '#loginForm > button:nth-child(10)';
        $this->driver->wait()->until(WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::cssSelector($submitButtonLoginCss)));

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
        $submitButtonLoginCss = '#loginForm > button:nth-child(10)';
        $this->driver->findElement(WebDriverBy::cssSelector($submitButtonLoginCss))->click();

        try {
            $signOutButtonCss= '#top-right-block > div.top-right-block.borderNone > div > span';
            $this->driver->wait()->until(WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::cssSelector($signOutButtonCss)));
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
            $signOutButtonCss= '#top-right-block > div.top-right-block.borderNone > div > span';
            $this->driver->findElement(WebDriverBy::cssSelector($signOutButtonCss));
            return true;

        } catch (NoSuchElementException $e) {

            return false;
        }
    }

} 