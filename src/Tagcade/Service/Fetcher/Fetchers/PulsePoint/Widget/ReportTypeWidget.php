<?php

namespace Tagcade\Service\Fetcher\Fetchers\PulsePoint\Widget;

use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverSelect;

class ReportTypeWidget extends AbstractWidget
{
    const OPTION_ACCOUNT_MANAGEMENT = '0';
    const OPTION_IMPRESSION_DOMAINS = '7';
    const OPTION_DAILY_STATS = '18';

    public function selectAccountManagement()
    {
        $this->getSelect()->selectByValue(static::OPTION_ACCOUNT_MANAGEMENT);
    }

    public function selectImpressionDomains()
    {
        $this->getSelect()->selectByValue(static::OPTION_IMPRESSION_DOMAINS);
    }

    public function selectDailyStats()
    {
        $this->getSelect()->selectByValue(static::OPTION_DAILY_STATS);
    }

    protected function getSelect()
    {
        return new WebDriverSelect($this->driver->findElement(WebDriverBy::id('ddlReportTypes')));
    }
}