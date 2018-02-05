<?php

namespace Tagcade\Service\Fetcher\Fetchers\CedatoInternal\Page;

use Exception;
use Facebook\WebDriver\Exception\NoSuchElementException;
use Facebook\WebDriver\Exception\TimeOutException;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Facebook\WebDriver\WebDriverWait;
use Tagcade\Service\Fetcher\Pages\AbstractCedatoHomePage;

class HomePage extends AbstractCedatoHomePage
{
    const URL = 'https://dashboard.cedato.com/#!/login';
    const LOG_OUT_URL = 'https://dashboard.cedato.com/#/login';

    /**
     * @param string $username
     * @param string $password
     * @return bool|mixed
     * @throws Exception
     * @throws NoSuchElementException
     * @throws TimeOutException
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

        $this->logger->debug('filling credentials');
        $this->driver->wait()->until(WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::id('username')));
        $this->driver->wait()->until(WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::id('password')));


        $this->driver
            ->findElement(WebDriverBy::id('username'))
            ->clear()
            ->sendKeys($username);

        $this->driver
            ->findElement(WebDriverBy::id('password'))
            ->clear()
            ->sendKeys($password);

        $this->logger->debug('click login button');
        $this->driver->findElement(WebDriverBy::id('loginBtn'))->click();

        return $this->isLoggedIn();
    }

    public function isLoggedIn()
    {
        $this->driver->wait(5);
        $dateRange = $this->driver->findElements(WebDriverBy::id('daterange'));

        return empty($dateRange) ? false : true;
    }

    public function doLogout()
    {
        $this->driver->navigate()->to(self::LOG_OUT_URL);
    }
}