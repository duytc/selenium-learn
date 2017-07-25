<?php

namespace Tagcade\Service\Fetcher;

use Facebook\WebDriver\Remote\RemoteWebDriver;
use Psr\Log\LoggerInterface;
use Tagcade\Exception\LoginFailException;
use Tagcade\Service\Core\TagcadeRestClientInterface;
use Tagcade\Service\DownloadFileHelperInterface;
use Tagcade\Service\Fetcher\Pages\AbstractHomePage;
use Tagcade\Service\Fetcher\Params\PartnerParamInterface;

abstract class PartnerFetcherAbstract implements PartnerFetcherInterface
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /** @var DownloadFileHelperInterface */
    protected $downloadFileHelper;

    /** @var TagcadeRestClientInterface */
    protected $tagcadeRestClient;

    /**
     * PartnerFetcherAbstract constructor.
     * @param LoggerInterface $logger
     * @param DownloadFileHelperInterface $downloadFileHelper
     * @param TagcadeRestClientInterface $tagcadeRestClient
     */
    public function __construct(LoggerInterface $logger, DownloadFileHelperInterface $downloadFileHelper, TagcadeRestClientInterface $tagcadeRestClient)
    {
        $this->logger = $logger;
        $this->downloadFileHelper = $downloadFileHelper;
        $this->tagcadeRestClient = $tagcadeRestClient;
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
            return;
        }

        /** @var AbstractHomePage $homePage */
        $homePage = $this->getHomePage($driver, $this->logger);
        $isLoggedIn = $homePage->doLogin($params->getUsername(), $params->getPassword());

        if (false == $isLoggedIn) {
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

    /**
     * @inheritdoc
     */
    public function doLogout(PartnerParamInterface $params, RemoteWebDriver $driver)
    {
        $this->logger->info(sprintf('Logging out for integration %s', $params->getIntegrationCName()));

        /** @var AbstractHomePage $homePage */
        $homePage = $this->getHomePage($driver, $this->logger);
        if (!$homePage->isLoggedIn()) {
            return;
        }

        try {
            $homePage->doLogout();
        } catch (\Exception $e) {

        }
    }

    /**
     * get homepage for login
     *
     * @param RemoteWebDriver $driver
     * @param LoggerInterface $logger
     * @return AbstractHomePage
     */
    abstract function getHomePage(RemoteWebDriver $driver, LoggerInterface $logger);
}