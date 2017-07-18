<?php

namespace Tagcade\Service\Fetcher\Fetchers\Lkqd\Page;


use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Facebook\WebDriver\WebDriverWait;
use Psr\Log\LoggerInterface;
use Tagcade\Exception\RuntimeException;
use Tagcade\Service\Core\TagcadeRestClientInterface;
use Tagcade\Service\Fetcher\Pages\AbstractHomePage;
use Tagcade\Service\Fetcher\Params\PartnerParamInterface;

class HomePage extends AbstractHomePage
{
    const URL = 'https://ui.lkqd.com/login';

    /**
     * @var PartnerParamInterface
     */
    private $partnerParams;

    /** @var TagcadeRestClientInterface */
    protected $tagcadeRestClient;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param PartnerParamInterface $partnerParams
     */
    public function setPartnerParams(PartnerParamInterface $partnerParams)
    {
        $this->partnerParams = $partnerParams;
    }

    /**
     * @param TagcadeRestClientInterface $tagcadeRestClient
     */
    public function setTagcadeRestClient($tagcadeRestClient)
    {
        $this->tagcadeRestClient = $tagcadeRestClient;
    }

    /**
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @inheritdoc
     */
    public function doLogin($username, $password)
    {
        $this->driver->manage()->timeouts()->pageLoadTimeout(30);
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
            ->sendKeys($username);

        $this->driver
            ->findElement(WebDriverBy::id('password'))
            ->clear()
            ->sendKeys($password);

        //$this->driver->manage()->timeouts()->setScriptTimeout(200);
        $this->driver->manage()->timeouts()->pageLoadTimeout(10);
        $this->logger->debug('click login button');
        $this->driver->findElement(WebDriverBy::tagName('button'))->click();

        $waitDriver = new WebDriverWait($this->driver, 60);
        try {
            // wait for login success
            $this->logger->debug('wait for login success');
            $waitDriver->until(WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::id('reports')));
        } catch (\Exception $e) {
            $this->logger->debug('could not found login success page, now try to find if have updating password page');

            // try to know if updating password page exists (and not login success )
            try {
                $this->driver->findElement(WebDriverBy::cssSelector('#update-password > div > div > div > div.form-box > div.button-box'));

                // send alert to UR API
                $this->createAlertToNotifyPasswordExpiry($this->partnerParams, self::URL);

                // do ignore updating password page
                $isIgnored = self::ignoreUpdatingPassword($this->driver, $this->logger);
                if (!$isIgnored) {
                    throw new RuntimeException('could not ignore updating password page, need try again');
                }

                return true;
            } catch (\Exception $ex) {
                // do nothing
                $this->logger->debug('could not found updating password page');
            }

            // not match 2 above cases => login faile => must return false
            $this->logger->debug('could not found both login success page and updating password page => login fail');
            return false;
        }

        return true;
    }

    public function isLoggedIn()
    {
        $reportings = $this->driver->findElements(WebDriverBy::id('reports'));

        return empty($reportings) ? false : true;
    }

    public function doLogout()
    {
        $this->driver->findElement(WebDriverBy::xpath('//ul[contains(@class, "navbar-right")]/li[contains(@class, "navigation-bar-item")]/div/a[contains(@class, "caret-button")]/span'))->click();

        $logOutButton = $this->filterElementByTagNameAndText('li', 'Logout');
        if ($logOutButton) {
            $logOutButton->click();
        }
        return $this;
    }

    /**
     * @param RemoteWebDriver $driver
     * @param LoggerInterface|null $logger
     * @return bool
     */
    public static function ignoreUpdatingPassword(RemoteWebDriver $driver, LoggerInterface $logger = null)
    {
        try {
            $waitDriver = new WebDriverWait($driver, 60);
            if ($driver->findElement(WebDriverBy::cssSelector('#update-password > div > div > div > div.form-box > div.button-box'))) {
                if ($logger instanceof LoggerInterface) {
                    $logger->debug('Password expiry, click update later');
                }

                $driver->findElement(WebDriverBy::cssSelector('#update-password > div > div > div > div.form-box > div.bottom-box > a'))->click();
                $logger->debug('Password expiry, click update later and then wait report page');
                $waitDriver->until(WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::id('reports')));
            }
        } catch (\Exception $e) {

            $logger->debug('Password expiry, could not click update later');

            return false;
        }

        return true;
    }

    /**
     * create Alert To Notify Password Expiry
     *
     * @param PartnerParamInterface $params
     * @param $url
     */
    private function createAlertToNotifyPasswordExpiry(PartnerParamInterface $params, $url)
    {
        $cname = $params->getIntegrationCName();
        $username = $params->getUsername();

        // create alert to notify customer update password
        $message = sprintf('Password expires on data source %s, please change password for username:  %s via URL %s', $params->getDataSourceId(), $username, $url);
        $this->tagcadeRestClient->createAlertWhenAppearUpdatePassword(
            $params->getPublisherId(),
            $integrationCName = $cname,
            $params->getDataSourceId(),
            $message,
            date_create(),
            $username,
            $url
        );
    }
}