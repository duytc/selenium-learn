<?php

namespace Tagcade\Service\Fetcher\Fetchers\PulsePoint\Page;

use DateTime;
use Facebook\WebDriver\Exception\NoSuchElementException;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Facebook\WebDriver\WebDriverSelect;
use Tagcade\Exception\InvalidDateRangeException;
use Tagcade\Service\Fetcher\Fetchers\PulsePoint\Widget\ExportButtonWidget;
use Tagcade\Service\Fetcher\Fetchers\PulsePoint\Widget\ReportSelectorWidget;
use Tagcade\Service\Fetcher\Pages\AbstractPage;

class ManagerPage extends AbstractPage
{
    const URL = 'https://exchange.pulsepoint.com/Publisher/Reports.aspx#/Reports';

    /**
     * @var ReportSelectorWidget
     */
    private $reportSelectorWidget;
    /**
     * @var ExportButtonWidget
     */
    private $exportButtonWidget;
    /**
     * @var String
     */
    protected $emailAddress;
    /**
     * @var Boolean
     */
    private $enableReceiveReportsByEmail;

    public function __construct(RemoteWebDriver $driver, ReportSelectorWidget $reportSelectorWidget, ExportButtonWidget $exportButtonWidget)
    {
        parent::__construct($driver);
        $this->reportSelectorWidget = $reportSelectorWidget;
        $this->exportButtonWidget = $exportButtonWidget;
    }

    /**
     * @return ReportSelectorWidget
     */
    public function getReportSelectorWidget()
    {
        return $this->reportSelectorWidget;
    }

    /**
     * @return ExportButtonWidget
     */
    public function getExportButtonWidget()
    {
        return $this->exportButtonWidget;
    }

    /**
     * The email address that will receive mailed reports
     *
     * @param $emailAddress
     * @return $this
     */
    public function setEmailAddress($emailAddress)
    {
        $this->emailAddress = $emailAddress;

        if ($this->enableReceiveReportsByEmail === null) {
            $this->enableReceiveReportsByEmail(true);
        }

        return $this;
    }

    /**
     * If this flag is set, if PulsePoint prompts us for an e-mail address to send reports too.
     * We will fill in the form and submit the form.
     *
     * If this is not set, we will just fill in the form without submitting which is useful for testing
     *
     * @param self
     * @return $this
     */
    public function enableReceiveReportsByEmail($bool)
    {
        $this->enableReceiveReportsByEmail = (bool)$bool;

        return $this;
    }

    /**
     * @param DateTime $startDate
     * @param DateTime $endDate
     * @return $this
     */
    public function getAccountManagementReport(DateTime $startDate, DateTime $endDate = null)
    {
        if ($this->hasLogger()) {
            $this->logger->info('Started to process Account Management report');
        }

        $this->getReportSelectorWidget()
            ->getReportTypeWidget()
            ->selectAccountManagement();

        $success = $this->getReport($startDate, $endDate);

        if ($this->hasLogger()) {
            if ($success) {
                $this->logger->info('Account Management report was retrieved successfully');
            } else {
                $this->logger->alert('Account Management report was not retrieved');
            }
        }

        return $this;
    }

    /**
     * Impression reports are only available for the last 7 days, if you get errors trying to select dates, this is the reason.
     * The UI enforces this restriction
     *
     * @param DateTime $startDate
     * @param DateTime $endDate
     * @return $this
     */
    public function getImpressionDomainsReports(DateTime $startDate, DateTime $endDate = null)
    {
        $this->getReportSelectorWidget()
            ->getReportTypeWidget()
            ->selectImpressionDomains();

        $adTagFilterElement = $this->driver->findElement(WebDriverBy::id('ddlAdTagGroupAndAdTags'));
        $adTagFilter = new WebDriverSelect($adTagFilterElement);
        $filterOptions = $adTagFilter->getOptions();

        $numberOfReports = count($filterOptions);

        foreach ($filterOptions as $index => $option) {
            $currentReportNumber = $index + 1;

            $optionText = $option->getText();

            if ($this->hasLogger()) {
                $this->logger->info(sprintf('Started to process Impression Domain report for %s (%d/%d)', $optionText, $currentReportNumber, $numberOfReports));
            }

            $this->driver->wait()->until(WebDriverExpectedCondition::refreshed(
                WebDriverExpectedCondition::visibilityOf($adTagFilterElement)
            ));

            $adTagFilter->selectByValue($option->getAttribute('value'));
            try {
                $success = $this->getReport($startDate, $endDate);
            } catch (InvalidDateRangeException $e) {
                continue;
            }

            if ($this->hasLogger()) {
                if ($success) {
                    $this->logger->info(sprintf('Impression Domain report for %s was retrieved successfully', $optionText));
                } else {
                    $this->logger->alert(sprintf('Impression Domain report for %s was not retrieved', $optionText));
                }
            }

            $this->sleep(1);
        }

        return $this;
    }

    /**
     * @param DateTime $startDate
     * @param DateTime $endDate
     * @return $this
     */
    public function getDailyStatsReport(DateTime $startDate, DateTime $endDate = null)
    {
        if ($this->hasLogger()) {
            $this->logger->info('Started to get Daily Stats report');
        }

        $this->getReportSelectorWidget()
            ->getReportTypeWidget()
            ->selectDailyStats();

        $success = $this->getReport($startDate, $endDate);

        if ($this->hasLogger()) {
            if ($success) {
                $this->logger->info('Daily Stats report was retrieved successfully');
            } else {
                $this->logger->alert('Daily Stats report was not retrieved');
            }
        }

        return $this;
    }

    protected function getReport(DateTime $startDate, DateTime $endDate = null)
    {
        $reportSelector = $this->getReportSelectorWidget();

        $reportSelector->getDateRangeWidget()
            ->setDateRange($startDate, $endDate);

        $reportSelector->getRunButtonWidget()
            ->clickButton();

        return $this->exportReport();
    }

    /**
     * @return bool
     * @throws NoSuchElementException
     * @throws \Exception
     * @throws \Facebook\WebDriver\Exception\TimeOutException
     */
    protected function exportReport()
    {
        $this->waitForData();

        try {
            $noDataMessage = $this->driver->findElement(WebDriverBy::cssSelector('.reportData .noImpressionDomainsDataContainer'));
            if ($noDataMessage->isDisplayed()) {
                if ($this->hasLogger()) {
                    $this->logger->info('There is no report data');
                }
                return false;
            }
        } catch (NoSuchElementException $e) {
        }

        try {
            $exportButton = $this->getExportButtonWidget();
            if ($exportButton->getElement()->isDisplayed()) {
                $exportButton->clickButton();
                if ($this->hasLogger()) {
                    $this->logger->info('Downloading report data');
                }
                return true;
            }
        } catch (NoSuchElementException $e) {
        }

        try {
            $emailField = $this->driver->findElement(WebDriverBy::name('txtEmail'));
            $submitButton = $this->driver->findElement(WebDriverBy::cssSelector('div.sendButton a.button'));
        } catch (NoSuchElementException $e) {
            return false;
        }

        if (!$this->emailAddress) {
            throw new \Exception('email address is not set');
        }

        $this->driver->wait()->until(WebDriverExpectedCondition::elementToBeClickable(WebDriverBy::cssSelector('.sendButton a.button')));

        $emailField
            ->clear()
            ->sendKeys($this->emailAddress);

        $this->sleep(1);

        if ($this->enableReceiveReportsByEmail) {
            if ($this->hasLogger()) {
                $this->logger->info(sprintf('Sending report via email to %s', $this->emailAddress));
            }

            $submitButton->click();

        } else {
            if ($this->hasLogger()) {
                $this->logger->info('Skipping form submit');
            }
        }

        return true;
    }
}