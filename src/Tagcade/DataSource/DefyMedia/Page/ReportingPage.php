<?php

namespace Tagcade\DataSource\DefyMedia\Page;

use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Tagcade\DataSource\DefyMedia\Widget\DateSelectWidget;
use Tagcade\DataSource\PulsePoint\Page\AbstractPage;

class ReportingPage extends AbstractPage
{
    const URL = 'https://pubportal.defymedia.com/app/report/publisher';

    public function getAllTagReports(\DateTime $startDate, \DateTime $endDate)
    {

        // step 0. select filter
        $this->driver->findElement(WebDriverBy::id('js-filter-row'))
            ->click()
        ;
        $this->driver->wait()->until(
            WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::id('report-date'))
        );
        // show report date selection
        $this->driver->findElement(WebDriverBy::id('report-date'))
            ->click()
        ;

        $this->driver->wait()->until(
            WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::cssSelector('.daterangepicker'))
        );


        // Step 1. Select date range
        $this->selectDateRange($startDate, $endDate);
        $this->driver->findElement(WebDriverBy::id('report-export'))
            ->click()
        ;

    }

    protected function selectDateRange(\DateTime $startDate, \DateTime $endDate)
    {
        $dateWidget = new DateSelectWidget($this->driver);
        $dateWidget->setDateRange($startDate, $endDate);
        return $this;
    }
} 