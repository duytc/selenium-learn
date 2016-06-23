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

        $this->logger->info('Select date range');
        $this->selectDateRange($startDate, $endDate);
        $this->selectBreakDown();
        $this->selectGroupBy();

        $this->logger->debug('Click Run Report button');
        $runReportButtonCss = '#button-1074';
        $this->driver->findElement(WebDriverBy::cssSelector($runReportButtonCss))->click();

        sleep(120);
        $downloadBtn =  $this->driver->findElement(WebDriverBy::cssSelector('a[title="Export to CSV"]'));
        $directoryStoreDownloadFile =  $this->getDirectoryStoreDownloadFile($startDate,$endDate,$this->getConfig());
        $this->downloadThenWaitUntilComplete($downloadBtn, $directoryStoreDownloadFile);
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
} 