<?php


namespace Tagcade\DataSource\Epom\Page;


use Facebook\WebDriver\Exception\TimeOutException;
use Facebook\WebDriver\Remote\RemoteWebElement;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Facebook\WebDriver\WebDriverSelect;
use Tagcade\DataSource\Epom\Widget\DateSelectWidget;
use Tagcade\DataSource\PulsePoint\Page\AbstractPage;


class Reportingpage extends AbstractPage {

    const URL     =     'https://www.epommarket.com/account/home.do#|publisherDashboard';


    const  BREAK_DOWN_GROUP_KICK_DOWN_INDEX          =   1;
    const  BREAK_DOWN_GROUP_UL_INDEX                 =   1;
    const  BREAK_DOWN_GROUP_DAY_OPTION_INDEX         =   1;

    const  GROUP_BY_GROUP_KICK_DOWN_INDEX            =   1;
    const  GROUP_BY_GROUP_UL_INDEX                   =   2;
    const  GROUP_BY_GROUP_SITE_INDEX                 =   0;
    const  GROUP_BY_GROUP_PLACEMENT_INDEX            =   2;

    /**
     * @param \DateTime $startDate
     * @param \DateTime $endDate
     * @throws TimeOutException
     * @throws \Exception
     * @throws \Facebook\WebDriver\Exception\NoSuchElementException
     * @throws null
     */
    public function getAllTagReports(\DateTime $startDate, \DateTime $endDate)
    {
        $this->logger->info('Clink to Analytic tab');
        $analyticCssSelector = '#tab-1015';
        $this->driver->findElement(WebDriverBy::cssSelector($analyticCssSelector))
            ->click()
        ;
        sleep(2);
        $this->logger->info('Select date range');
        $this->selectDateRange($startDate, $endDate);
        $this->selectBreakDown();
        $this->selectGroupBy();

        $this->logger->debug('Find and Click Report button');
        $this->findRunReportButtonAndClick();

        $this->waitLoadingBodyReport();
        $this->waitLoadingSummaryReport();

        $downloadBtn =  $this->driver->findElement(WebDriverBy::cssSelector('a[title="Export to CSV"]'));
        $directoryStoreDownloadFile =  $this->getDirectoryStoreDownloadFile($startDate,$endDate,$this->getConfig());
        $this->downloadThenWaitUntilComplete($downloadBtn, $directoryStoreDownloadFile);
        $this->waitDownloadIfNotCompleteInHelper();
    }

    /**
     * @param \DateTime $startDate
     * @param \DateTime $endDate
     * @return $this
     */
    protected function selectDateRange(\DateTime $startDate, \DateTime $endDate)
    {
        $dateWidget = new DateSelectWidget($this->driver, $this->logger);
        $dateWidget->setDateRange($startDate, $endDate);

        return $this;
    }

    /**
     * @throws \Facebook\WebDriver\Exception\NoSuchElementException
     */
    protected function selectBreakDown()
    {
        $this->logger->debug('Click to Break down button.');

        $breakDownTableId = 'analytics-groupRange-triggerWrap';
        $breakDownTableElement =  $this->driver->findElement(WebDriverBy::id($breakDownTableId));
        $tdElements = $breakDownTableElement->findElements(WebDriverBy::cssSelector('td'));
        $tdElements[self::BREAK_DOWN_GROUP_KICK_DOWN_INDEX]->click();

        $this->logger->debug('Choose Day option');
        $ulElements = $this->driver->findElements(WebDriverBy::cssSelector('ul[class="x-list-plain"]'));
        $liElements = $ulElements[self::BREAK_DOWN_GROUP_UL_INDEX]->findElements(WebDriverBy::cssSelector('li[role="option"]'));
        $liElements[self::BREAK_DOWN_GROUP_DAY_OPTION_INDEX]->click();
    }

    /**
     * Select group by option in report
     */
    protected function selectGroupBy()
    {
        $this->logger->debug('Select Group By option');

        $groupByTableId = 'analytics-groupBy-triggerWrap';
        $groupByTableElement = $this->driver->findElement(WebDriverBy::id($groupByTableId));
        $tdElements = $groupByTableElement->findElements(WebDriverBy::cssSelector('td'));
        $tdElements[self::GROUP_BY_GROUP_KICK_DOWN_INDEX]->click();

        $this->logger->debug('Choose Site and Placement option');
        $ulElements = $this->driver->findElements(WebDriverBy::cssSelector('ul[class="x-list-plain"]'));
        $liElements = $ulElements[self::GROUP_BY_GROUP_UL_INDEX]->findElements(WebDriverBy::cssSelector('li[role="option"]'));
        $liElements[self::GROUP_BY_GROUP_SITE_INDEX]->click();
        $liElements[self::GROUP_BY_GROUP_PLACEMENT_INDEX]->click();
    }


    protected function findRunReportButtonAndClick()
    {
        $analyticsFormElement = $this->driver->findElement(WebDriverBy::id('analytics-form-id'));
        $aElementsCss = 'a[class="x-btn x-unselectable btn btn-default btn-lg x-box-item x-toolbar-item x-btn-default-medium x-noicon x-btn-noicon x-btn-default-medium-noicon"]';
        $aElements = $analyticsFormElement->findElements(WebDriverBy::cssSelector($aElementsCss));
        $this->logger->debug(sprintf('Number a element =%d', count($aElements)));
        $aElements[1]->click();
    }

    /**
     * Waiting for loading report finish
     */
    protected function waitLoadingBodyReport()
    {
        $report1GridElement =  $this->driver->findElement(WebDriverBy::id('report1Grid'));
        $divLoadingElements = $report1GridElement->findElement(WebDriverBy::cssSelector('div[class="x-component x-mask-msg x-component-default"]'));

        $totalWaitingTime = 0;
        do {
            sleep(5);
            $totalWaitingTime +=5;
            $styleValue = $divLoadingElements->getAttribute('style');
            $isNoneValueInStyle = strpos($styleValue,'display: none;');

            $this->logger->debug(sprintf('Report in detail waiting: Css Value %s', $styleValue ));
            $this->logger->debug(sprintf('Report in detail waiting: $isNoneValueInStyle Value %d', $isNoneValueInStyle ));
            $this->logger->debug(sprintf('Report in detail waiting: Total waiting time: %d', $totalWaitingTime ));

        } while (false == $isNoneValueInStyle);
    }

    /**
     * Waiting for loading report finish class="x-mask-msg"
     */
    protected function waitLoadingSummaryReport()
    {
        $report1GridElement =  $this->driver->findElement(WebDriverBy::id('report1Tab-body'));
        $divLoadingElements = $report1GridElement->findElement(WebDriverBy::cssSelector('div[class="x-component x-mask-msg x-component-default"]'));

        $totalWaitingTime = 0;
        do {
            sleep(5);
            $totalWaitingTime +=5;
            $styleValue = $divLoadingElements->getAttribute('style');
            $isNoneValueInStyle = strpos($styleValue,'display: none;');

            $this->logger->debug(sprintf('Summary Report Waiting: Css Value %s', $styleValue ));
            $this->logger->debug(sprintf('Summary Report Waiting: $isNoneValueInStyle Value %d', $isNoneValueInStyle ));
            $this->logger->debug(sprintf('Summary Report Waiting: Total waiting time: %d', $totalWaitingTime ));

        } while (false == $isNoneValueInStyle);
    }

    /**
     * Waiting for loading report finish
     * @param int $timeout
     */
    protected function waitDownloadIfNotCompleteInHelper($timeout = 120)
    {
        $report1GridElement =  $this->driver->findElement(WebDriverBy::id('report1Grid'));

        $divLoadingElements = $report1GridElement->findElements(WebDriverBy::cssSelector('div[class="x-mask-msg"]'));
        $countLoadingElements = count($divLoadingElements);
        $totalWaitingTime = 0;

        while (($countLoadingElements > 0) && ($totalWaitingTime < $timeout)) {
            sleep(5);
            $totalWaitingTime +=5;

            $divLoadingElements = $report1GridElement->findElements(WebDriverBy::cssSelector('div[class="x-mask-msg"]'));
            $countLoadingElements = count($divLoadingElements);

            $this->logger->debug(sprintf('Wait Download complete After in Helper: Count loading element %d', $countLoadingElements));
            $this->logger->debug(sprintf('Wait Download complete After in Helper: Total waiting time: %d', $totalWaitingTime ));
        }
    }
} 