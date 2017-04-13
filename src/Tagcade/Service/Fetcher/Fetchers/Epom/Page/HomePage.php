<?php


namespace Tagcade\Service\Fetcher\Fetchers\Epom\Page;


use Facebook\WebDriver\Exception\NoSuchElementException;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Tagcade\Service\Fetcher\Pages\AbstractHomePage;

class HomePage extends AbstractHomePage
{
    const URL = 'http://www.epommarket.com';

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

        $this->logger->debug('Filling username and password');

        $submitButtonLoginCss = '#loginForm > button:nth-child(10)';
        $this->driver->wait()->until(WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::cssSelector($submitButtonLoginCss)));

        $this->driver
            ->findElement(WebDriverBy::id('username'))
            ->clear()
            ->sendKeys($username);

        $this->driver
            ->findElement(WebDriverBy::id('password'))
            ->clear()
            ->sendKeys($password);

        $this->logger->debug('Click login button');
        $submitButtonLoginCss = '#loginForm > button:nth-child(10)';
        $this->driver->findElement(WebDriverBy::cssSelector($submitButtonLoginCss))->click();

        sleep(2);
        return $this->isLoggedIn();
    }

    /**
     * @return bool
     */
    public function isLoggedIn()
    {
        $signOutButtonCss = '#top-right-block > div.top-right-block.borderNone > div > span';
        #top-right-block > div:nth-child(5) > div > b
        $signOutElements = $this->driver->findElements(WebDriverBy::cssSelector($signOutButtonCss));

        return empty($signOutElements) ? false : true;
    }
}