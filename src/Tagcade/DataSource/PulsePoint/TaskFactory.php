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

class TaskFactory
{
    public static function getAllData(RemoteWebDriver $driver, TaskParams $params, LoggerInterface $logger = null)
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

        if ($logger) {
            $managerPage->setLogger($logger);
            $loginPage->setLogger($logger);
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
}