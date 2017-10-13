<?php

namespace Tagcade\Service\Fetcher\Fetchers\VertaInternal\Page;

use Exception;
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

        while (1) {
            $emails = $this->driver->findElements(WebDriverBy::name('email'));
            $this->logger->debug('waiting for email input visible');
            if (!empty($emails)) {
                break;
            }
        }

        $this->logger->debug('filling credentials');

        try {
            $this->driver->wait(100, 1000)->until(WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::name('email')));
        } catch (Exception $e) {
            return false;
        }

        $this->driver
            ->findElement(WebDriverBy::name('email'))
            ->clear()
            ->sendKeys($username);

        $this->sleep(1);
        $nextBtn = $this->filterElementByTagNameAndText('button', 'NEXT');
        $nextBtn->click();

        try {
            $this->driver->wait(30)->until(WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::name('password')));
        } catch (Exception $e) {
            $this->logger->debug('Login fail maybe due to username is wrong format. Please check username again.');
            return false;
        }

        $this->driver
            ->findElement(WebDriverBy::name('password'))
            ->clear()
            ->sendKeys($password);

        $this->sleep(1);
        $this->logger->debug('click login button');
        $signIn = $this->filterElementByTagNameAndText('button', 'SIGN IN');
        $signIn->click();

        $this->driver->manage()->timeouts()->pageLoadTimeout(60);
        $waitDriver = new WebDriverWait($this->driver, 60);

        try {
            $waitDriver->until(WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::cssSelector('application > div > application-menu > div > div > nav > application-menu-user > div > div > span.vd-description__name.vd-wrap')));
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
        } catch (Exception $ne) {
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
        } catch (Exception $ne) {

        }

        return false;
    }
}