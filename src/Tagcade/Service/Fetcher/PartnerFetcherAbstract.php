<?php

namespace Tagcade\Service\Fetcher;

use Facebook\WebDriver\Remote\RemoteWebDriver;
use Psr\Log\LoggerInterface;
use Tagcade\Exception\LoginFailException;
use Tagcade\Service\DownloadFileHelperInterface;
use Tagcade\Service\Fetcher\Pages\AbstractHomePage;
use Tagcade\Service\Fetcher\Params\PartnerParamInterface;

abstract class PartnerFetcherAbstract implements PartnerFetcherInterface
{
    const REPORT_PAGE_URL = '';
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /** @var DownloadFileHelperInterface */
    protected $downloadFileHelper;

    /**
     * PartnerFetcherAbstract constructor.
     * @param LoggerInterface $logger
     * @param DownloadFileHelperInterface $downloadFileHelper
     */
    public function __construct(LoggerInterface $logger, DownloadFileHelperInterface $downloadFileHelper)
    {
        $this->logger = $logger;
        $this->downloadFileHelper = $downloadFileHelper;
    }

    /**
     * @inheritdoc
     */
    public function getDownloadFileHelper()
    {
        return $this->downloadFileHelper;
    }

    /**
     * @inheritdoc
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * @inheritdoc
     */
    public function doLogin(PartnerParamInterface $params, RemoteWebDriver $driver, $needToLogin = false)
    {
        $this->logger->info(sprintf('Entering login page for integration %s', $params->getIntegrationCName()));

        if (!$needToLogin) {
            $driver->navigate()->to($this->getReportPageUrl());
            return;
        }

        /** @var AbstractHomePage $homePage */
        $homePage = $this->getHomePage($driver, $this->logger);
        $isLogin = $homePage->doLogin($params->getUsername(), $params->getPassword());

        if (false == $isLogin) {
            $this->logger->warning(sprintf('Login system failed for integration %s', $params->getIntegrationCName()));

            // critical error
            // do not call driver quit here, this is already called when handle exception for retry
            // TODO: remove when stable
            // $driver->quit();
            // end notice

            throw new LoginFailException(
                $params->getPublisherId(),
                $params->getIntegrationCName(),
                $params->getDataSourceId(),
                $params->getStartDate(),
                $params->getEndDate(),
                new \DateTime()
            );
        }
    }

    public function doLogout(PartnerParamInterface $params, RemoteWebDriver $driver)
    {
        $this->logger->info(sprintf('Logging out for integration %s', $params->getIntegrationCName()));

        /** @var AbstractHomePage $homePage */
        $homePage = $this->getHomePage($driver, $this->logger);
        if (!$homePage->isLoggedIn()) {
            return;
        }

        $homePage->doLogout();
    }


    /**
     * get homepage for login
     *
     * @param RemoteWebDriver $driver
     * @param LoggerInterface $logger
     * @return AbstractHomePage
     */
    abstract function getHomePage(RemoteWebDriver $driver, LoggerInterface $logger);

    protected function getReportPageUrl()
    {
        return static::REPORT_PAGE_URL;
    }
}