<?php
namespace Tagcade\Service\Fetcher\Fetchers\Lkqd\Page;

use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Tagcade\Service\Fetcher\Fetchers\PulsePoint\Page\AbstractPage;

class HomePage extends AbstractPage
{
    const URL = 'https://ui.lkqd.com/login';

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
        $this->driver->wait()->until(WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::tagName('button')));

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

        $this->driver->manage()->timeouts()->setScriptTimeout(200);
        $this->driver->manage()->timeouts()->pageLoadTimeout(200);
        $this->logger->debug('click login button');
        $this->driver->findElement(WebDriverBy::tagName('button'))->click();

        $flash = $this->driver->findElement(WebDriverBy::cssSelector('div[id="flash"]'));
        if ($flash) {
            $error = $flash->findElement(WebDriverBy::tagName('div'));
            if ($error) {
                $class = $error->getAttribute('class');
                if (strpos('alert-warning', $class)) {
                    return false;
                }

                return true;
            }

            return true;
        }

        return true;
    }

    protected function isLoggedIn()
    {
        $reportings = $this->driver->findElements(WebDriverBy::id('reports'));

        return empty($reportings)? false:true;
    }
}