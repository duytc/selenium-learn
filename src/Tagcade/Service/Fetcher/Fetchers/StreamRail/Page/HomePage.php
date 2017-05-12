<?php

namespace Tagcade\Service\Fetcher\Fetchers\StreamRail\Page;

use Exception;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Facebook\WebDriver\WebDriverWait;
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

        $this->sleep(2);

        $this->logger->debug('filling credentials');

        $index = 0;
        $usernameId = 717;

        do {
            try {
                $id = $this->ids[$index];
                $this->driver->findElement(WebDriverBy::id(sprintf('ember%s-input', $id)));
                $usernameId = $id;
                break;
            } catch (\Exception $e) {
                $index++;
            }
        } while ($index < count($this->ids));

        $this->driver
            ->findElement(WebDriverBy::id(sprintf('ember%s-input', $usernameId)))
            ->clear()
            ->sendKeys($username);

        $this->driver
            ->findElement(WebDriverBy::id(sprintf('ember%s-input', $usernameId + 21)))
            ->clear()
            ->sendKeys($password);

        $this->logger->debug('click login button');
        $this->driver->findElement(WebDriverBy::className('action-btn'))->click();
        sleep(2);
        $this->driver->manage()->timeouts()->pageLoadTimeout(60);
        $waitDriver = new WebDriverWait($this->driver, 20);

        try {
            $waitDriver->until(WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::id('main-container')));

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public function isLoggedIn()
    {
        $headerMainmenus = $this->driver->findElements(WebDriverBy::id('ember1172'));

        return empty($headerMainmenus) ? false : true;
    }
}