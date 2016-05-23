<?php

namespace Tagcade\DataSource\YellowHammer\Page;

use Facebook\WebDriver\Exception\NoSuchElementException;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Facebook\WebDriver\WebDriverSelect;
use Tagcade\DataSource\PulsePoint\Exception\InvalidDateRangeException;
use Tagcade\DataSource\PulsePoint\Page\AbstractPage;
use Tagcade\DataSource\YellowHammer\Widget\DateSelectWidget;

class ReportingPage extends AbstractPage
{
    const URL = 'http://publishers.yhmg.com/reporting';

    public function getAllTagReports(\DateTime $startDate, \DateTime $endDate)
    {
        $this->info('select date range');
        $this->driver->wait()->until(
            WebDriverExpectedCondition::elementToBeClickable(WebDriverBy::id('s2id_time_period'))
        );
        
        $timePeriodElement = $this->driver
            ->findElement(WebDriverBy::id('s2id_time_period'));

        if ($timePeriodElement->getText() != 'Custom') {
            $timePeriodElement->click();
            $this->driver->wait()->until(
                WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::cssSelector('option[value=custom]'))
            );
        }

        $customDateElement = $this->driver
            ->findElement(WebDriverBy::id('time_period'))
        ;

        (new WebDriverSelect($customDateElement))->selectByValue('custom');

        $this->driver->wait()->until(WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::id('start_date')));

        $this->selectDateRange($startDate, $endDate);

        // Select timezone

        $selectedTimeZone = $this->driver
            ->findElement(WebDriverBy::cssSelector('#s2id_timezone a'))
            ->getText()
        ;

        if (strcasecmp($selectedTimeZone, 'US/Pacific') != 0) {
            $this->driver
                ->findElement(WebDriverBy::id('s2id_timezone'))
                ->click()
            ;

            $this->driver->wait()->until(
                WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::id('timezone'))
            );

            $timeZoneElement = $this->driver->findElement(WebDriverBy::id('timezone'));
            (new WebDriverSelect($timeZoneElement))
                ->selectByValue('US/Pacific');
        }

        // Select tag breakdown
        $this->driver->findElement(WebDriverBy::cssSelector('.tag'))
            ->click()
        ;

        // click run reports
        $this->driver->findElement(WebDriverBy::cssSelector('#builder .report-btn > button+button'))
            ->click()
        ;

        $this->info('exporting reports');
        $exportActions = $this->driver->findElements(WebDriverBy::cssSelector('#builder .dropdown-menu a'));
        $exportCSV = null;
        foreach($exportActions as $link) {
            $txt = $link->getText();
            if ($txt == 'Export to CSV') {
                $exportCSV = $link;
                break;
            }
        }

        $this->driver->wait()->until(WebDriverExpectedCondition::visibilityOf($exportCSV));
        //$exportCSV->click();
        $this->downloadThenWaitUntilComplete($exportCSV);

        //button-loader
        $this->driver->manage()->timeouts()->implicitlyWait(0);
        $this->driver->wait()->until(WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::id('notification')));

        return $this;
    }

    protected function selectDateRange(\DateTime $startDate, \DateTime $endDate)
    {
        $dateWidget = new DateSelectWidget($this->driver);
        $dateWidget->setDate($startDate, 'start_date');
        $dateWidget->setDate($endDate, 'end_date');

        return $this;
    }
} 