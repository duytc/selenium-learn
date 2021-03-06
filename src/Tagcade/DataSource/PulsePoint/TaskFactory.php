<?php

namespace Tagcade\DataSource\PulsePoint;

use DateTime;
use Facebook\WebDriver\Remote\RemoteWebDriver;

use Psr\Log\LoggerInterface;
use Tagcade\DataSource\PulsePoint\Page\LoginPage;
use Tagcade\DataSource\PulsePoint\Page\ManagerPage;
use Tagcade\DataSource\PulsePoint\Widget\ExportButtonWidget;
use Tagcade\DataSource\PulsePoint\Widget\ReportSelectorWidget;
use Tagcade\DataSource\PulsePoint\Widget\ReportTypeWidget;
use Tagcade\DataSource\PulsePoint\Widget\DateRangeWidget;
use Tagcade\DataSource\PulsePoint\Widget\RunButtonWidget;
use Tagcade\WebDriverFactoryInterface;

class TaskFactory implements TaskFactoryInterface
{
    /**
     * @var WebDriverFactoryInterface
     */
    private $webDriverFactory;
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(WebDriverFactoryInterface $webDriverFactory, LoggerInterface $logger = null)
    {
        $this->webDriverFactory = $webDriverFactory;
        $this->logger = $logger;
    }

    public function getWebDriverFactory()
    {
        return $this->webDriverFactory;
    }

    public function getAllData(TaskParams $params,RemoteWebDriver $driver = null)
    {
        $reportSelectorWidget = new ReportSelectorWidget(
            $driver,
            new ReportTypeWidget($driver),
            new DateRangeWidget($driver),
            new RunButtonWidget($driver)
        );

        $exportButtonWidget = new ExportButtonWidget($driver);

        $managerPage = new ManagerPage($driver, $reportSelectorWidget, $exportButtonWidget);
        $loginPage = new LoginPage($driver);

        if ($this->logger) {
            $managerPage->setLogger($this->logger);
            $loginPage->setLogger($this->logger);
        }

        if (!$managerPage->isCurrentUrl()) {
            $managerPage->navigate();

            if ($loginPage->isCurrentUrl()) {
                $loginPage->login($params->getUsername(), $params->getPassword());
            }

            $managerPage->waitForData();
        }

        $managerPage->enableReceiveReportsByEmail($params->getReceiveReportsByEmail());

        $reportDate = $params->getReportDate() ?: new DateTime('yesterday');

        $managerPage
            ->setEmailAddress($params->getEmailAddress())
            ->getAccountManagementReport($reportDate)
            ->getDailyStatsReport($reportDate)
            ->getImpressionDomainsReports($reportDate)
        ;
    }

    public function createParams($username, $password, $email, DateTime $date)
    {
        return (new TaskParams())
            ->setUsername($username)
            ->setPassword($password)
            ->setEmailAddress($email)
            ->setReportDate($date)
        ;
    }
}