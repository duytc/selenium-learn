<?php

namespace Tagcade\DataSource\PulsePoint\Page;

use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Tagcade\DataSource\PulsePoint\Widget\DateRangeWidget;
use Tagcade\DataSource\PulsePoint\Widget\RunButtonWidget;

class ReportPage extends AbstractPage
{
    const URL = 'https://exchange.pulsepoint.com/Publisher/Reports.aspx#/Reports';

    public function getAllTagReports(\DateTime $startDate, \DateTime $endDate = null)
    {
        // Step 1. Select date range
        $this->logger->debug('select date range');
        $reportDetailBox = $this->driver->findElement(WebDriverBy::id('reportDDLContainer'));
        if (!$reportDetailBox->isDisplayed()) {
            $reportDetailsHeaderSel = WebDriverBy::cssSelector('.header-title');

            $this->driver->wait()->until(WebDriverExpectedCondition::elementToBeClickable($reportDetailsHeaderSel));

            // select
            $this->driver->findElement($reportDetailsHeaderSel)
                ->click()
            ;

            $this->driver->wait()->until(WebDriverExpectedCondition::elementToBeClickable(WebDriverBy::id('dateFrom')));
        }

        $this->logger->debug('filling start date data');
        $this->driver->findElement(WebDriverBy::id('dateFrom'))
            ->clear()
            ->sendKeys($startDate->format('m/d/Y'))
        ;

        usleep(200);
        $this->logger->debug('filling end date data');
        $this->driver->findElement(WebDriverBy::id('dateTo'))
            ->clear()
            ->sendKeys($startDate->format('m/d/Y'))
        ;
        usleep(200);

        // run report
        $this->logger->debug('click run report button');
        $this->driver->findElement(WebDriverBy::id('btnContainer'))
            ->click()
        ;

        $this->driver->wait()->until(
            WebDriverExpectedCondition::not(WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::cssSelector('.block-ui-overlay')))
        );

        $this->driver->wait()->until(
            WebDriverExpectedCondition::not(WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::cssSelector('.block-ui-message-container')))
        );

        $this->driver->wait(30, 1000)->until(
            WebDriverExpectedCondition::not(WebDriverExpectedCondition::presenceOfAllElementsLocatedBy(WebDriverBy::cssSelector('div.blockUI')))
        );

        $this->driver->wait()->until(WebDriverExpectedCondition::elementToBeClickable(WebDriverBy::cssSelector('.exportBtn')));
        $exportButton = $this->driver->findElement(WebDriverBy::cssSelector('.exportBtn'));
        // click export to excel
        $this->logger->debug('start downloading reports');
        //$exportButton->click();
        $this->downloadThenWaitUntilComplete($exportButton);
        $this->logger->debug('Clicked downloading reports');

    }
} 