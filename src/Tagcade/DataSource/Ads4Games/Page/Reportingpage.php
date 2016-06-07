<?php


namespace Tagcade\DataSource\Ads4Games\Page;


use Facebook\WebDriver\Exception\TimeOutException;
use Facebook\WebDriver\Remote\RemoteWebElement;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Facebook\WebDriver\WebDriverSelect;
use Tagcade\DataSource\Ads4Games\Widget\DateSelectWidget;
use Tagcade\DataSource\PulsePoint\Page\AbstractPage;

class Reportingpage extends AbstractPage {


    const URL = 'https://traffic.a4g.com/www/admin/plugins/advancedStats/advancedStats-trafficker.php?entity=web';
    const OPTION_SPECIFIC_VALUE = 'specific';

    public function getAllTagReports(\DateTime $startDate, \DateTime $endDate)
    {

        $this->selectDateRange($startDate, $endDate);

        $goButtonCssSelector = '#period_form > input:nth-child(9)';
        $this->driver->findElement(WebDriverBy::cssSelector($goButtonCssSelector))
            ->click()
        ;

        $this->waitForJquery();
        sleep(2);

        $this->driver->wait()->until(
            WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::cssSelector('#thirdLevelContent > table.tableStats > tbody:nth-child(3)'))
        );

        $actionCssSelector = '#thirdLevelTools > ul > li > div > span > span';
        $this->driver->findElement(WebDriverBy::cssSelector($actionCssSelector))
            ->click()
        ;

        $zoneCssSelector = '.panel > div:nth-child(1) > ul:nth-child(1) > li:nth-child(2) > a:nth-child(1)';
        $this->driver->wait()->until(
            WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::cssSelector($zoneCssSelector))
        );
        $downloadBtn =  $this->driver->findElement(WebDriverBy::cssSelector($zoneCssSelector));

        $this->downloadThenWaitUntilComplete($downloadBtn);

    }

    protected function selectDateRange(\DateTime $startDate, \DateTime $endDate)
    {
        $dateWidget = new DateSelectWidget($this->driver);
        $dateWidget->setDateRange($startDate, $endDate);
        return $this;
    }

} 