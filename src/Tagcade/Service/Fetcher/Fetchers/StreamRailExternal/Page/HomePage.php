<?php

namespace Tagcade\Service\Fetcher\Fetchers\StreamRailExternal\Page;

use Exception;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Facebook\WebDriver\WebDriverWait;
use Tagcade\Exception\LoginFailException;
use Tagcade\Service\Fetcher\Pages\AbstractHomePage;

class HomePage extends AbstractHomePage
{
    const URL = 'http://partners.streamrail.com/';
    protected $ids = [717, 730];

    public function doLogin($username, $password)
    {
        $this->driver->manage()->timeouts()->pageLoadTimeout(30);
        $this->driver->navigate()->to(self::URL);

        if ($this->isLoggedIn()) {
            return true;
        }

        if (!$this->isCurrentUrl()) {
            $this->navigate();
        }

        $this->sleep(4);

        $this->logger->debug('filling credentials');

        $inputFields = $this->driver->findElements(WebDriverBy::cssSelector(".sr-signin--wrapper .input-element input"));

        if (count($inputFields) != 2) {
            // should throw login fail exception here since input fields are not correct
            //return false and the system will throw exception in the next step
            $this->logger->notice('Invalid username or password field on HTML');
            return false;
        }

        $usernameField = $inputFields[0];
        $passwordField = $inputFields[1];

        $usernameField
            ->clear()
            ->sendKeys($username);

        $passwordField
            ->clear()
            ->sendKeys($password);

        $this->logger->debug('click login button');
        $this->driver->findElement(WebDriverBy::className('action-btn'))->click();
        sleep(4);
        $this->driver->manage()->timeouts()->pageLoadTimeout(60);
        $waitDriver = new WebDriverWait($this->driver, 20);

        try {
            $waitDriver->until(WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::id('main-container')));
            $userNotFound = $this->filterElementByTagNameAndText('small', 'User not found');
            $invalidUserNamePassword = $this->filterElementByTagNameAndText('small', 'Invalid username or password');
            if (!empty($userNotFound)) {
                // should throw login fail exception here and provide a reason
                $this->logger->debug('User not found');
                return false;
            }

            if (!empty($invalidUserNamePassword)) {
                // should throw login fail exception here and provide a reason
                $this->logger->notice('Invalid username or password');
                return false;
            }

            return true;
        } catch (Exception $e) {
            $this->logger->debug($e);
            return false;
        }
    }

    public function isLoggedIn()
    {
        $headerMainmenus = $this->driver->findElements(WebDriverBy::id('ember1172'));

        return empty($headerMainmenus) ? false : true;
    }

    public function doLogout()
    {
        $pageSource = $this->driver->getPageSource();
        $posAvatar = strpos($pageSource, 'sr-account-image full-height ember-view');
        $idAvatar = substr($pageSource, $posAvatar - 13, 4);

        if (!(int)$idAvatar) {
            $idAvatar = 1581;
        }

        try {
            $this->driver->findElement(WebDriverBy::cssSelector(sprintf('#ember%s', $idAvatar)))->click();
        } catch (\Exception $e) {
            $this->driver->findElement(WebDriverBy::cssSelector('#ember1163'))->click();
        }

        $logOutElement = $this->filterElementByTagNameAndText('li', 'Log out');
        if ($logOutElement) {
            $logOutElement->click();
        }
    }
}