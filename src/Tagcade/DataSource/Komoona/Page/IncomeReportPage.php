<?php

namespace Tagcade\DataSource\Komoona\Page;

use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Tagcade\DataSource\Komoona\Widget\DateSelectWidget;
use Tagcade\DataSource\PulsePoint\Page\AbstractPage;

class IncomeReportPage extends AbstractPage
{
    const URL = 'https://www.komoona.com/reports/income';

    public function __construct(RemoteWebDriver $driver)
    {
        parent::__construct($driver);
    }

    public function getAllTagReports(\DateTime $startDate, \DateTime $endDate = null)
    {
        // Step 1. Select date range
        $this->selectDateRange($startDate, $endDate);
        $this->driver->findElement(WebDriverBy::id('get-tags-reprot'))
            ->click()
        ;

        $this->driver->wait()->until(
            WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::id('tags-export-to-excel'))
        );

        $this->driver->findElement(WebDriverBy::id('tags-export-to-excel'))
            ->click()
        ;
    }

    protected function selectDateRange(\DateTime $startDate, \DateTime $endDate = null)
    {
        if ($endDate == null) {
            $endDate = $startDate;
        }

        if ($startDate > $endDate) {
            throw new \InvalidArgumentException('The date range supplied is invalid');
        }

        $dateWidget = new DateSelectWidget($this->driver);
        $dateWidget->setDate($startDate, 'select#tags-date+input+img');
        $dateWidget->setDate($endDate, 'select#tags-date+input+img+input+img');

        return $this;
    }
}