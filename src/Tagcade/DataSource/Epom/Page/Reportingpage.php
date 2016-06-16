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

        sleep(3);

        $this->logger->info('Select date range');
        $this->selectDateRange($startDate, $endDate);
        $this->selectBreakDown();
        $this->selectGroupBy();

        $this->logger->info('Running report');
        $runReportButtonCss = '#button-1074';
        $this->driver->findElement(WebDriverBy::cssSelector($runReportButtonCss))
            ->click()
        ;

        $reportDetail = '#report1Grid_header_hd-textEl';

        $this->driver->wait()->until(
            WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::cssSelector($reportDetail))
        );

        $this->driver->wait()->until(
            WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::cssSelector('a[title="Export to CSV"]'))
        );

        $downloadBtn =  $this->driver->findElement(WebDriverBy::cssSelector('a[title="Export to CSV"]'));

        $this->downloadThenWaitUntilComplete($downloadBtn);
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
        $breakDownCss = '#ext-gen1253';
        $this->driver->findElement(WebDriverBy::cssSelector($breakDownCss))->click();

        $dayElement = '#boundlist-1089-listEl > ul > li:nth-child(2)';
        $this->driver->findElement(WebDriverBy::cssSelector($dayElement))->click();
    }

    protected function selectGroupBy()
    {
        $groupByCss = '#ext-gen1256';
        $this->driver->findElement(WebDriverBy::cssSelector($groupByCss))->click();

        $siteCss = '#boundlist-1090-listEl > ul > li:nth-child(1)';
        $this->driver->findElement(WebDriverBy::cssSelector($siteCss))->click();

        $placementCss = '#boundlist-1090-listEl > ul > li:nth-child(3)';
        $this->driver->findElement(WebDriverBy::cssSelector($placementCss))->click();

        sleep(2);
    }
} 