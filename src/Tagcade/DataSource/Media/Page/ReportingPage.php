<?php

namespace Tagcade\DataSource\Media\Page;

use Facebook\WebDriver\Exception\NoSuchElementException;
use Facebook\WebDriver\Exception\TimeOutException;
use Facebook\WebDriver\JavaScriptExecutor;
use Facebook\WebDriver\Remote\RemoteWebElement;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Tagcade\DataSource\Media\Widget\DateSelectWidget;
use Tagcade\DataSource\PulsePoint\Page\AbstractPage;

class ReportingPage extends AbstractPage
{
    const URL = 'https://control.media.net/reports';

    public function getAllTagReports(\DateTime $startDate, \DateTime $endDate)
    {
        // step 0. Report tab
        $this->driver->findElement(WebDriverBy::id('reports'))
            ->click();
        $this->driver->wait()->until(
            WebDriverExpectedCondition::presenceOfAllElementsLocatedBy(WebDriverBy::id('reports'))
        );
        $this->driver->findElement(WebDriverBy::id('AdTags'))
            ->click();

        $this->driver->wait()->until(
            WebDriverExpectedCondition::presenceOfAllElementsLocatedBy(WebDriverBy::id('adTagStatsTab'))
        );

        $this->selectDateRange($startDate, $endDate);
        $this->driver->findElement(WebDriverBy::id('btnGo'))->click();

        $this->driver->wait()->until(
            WebDriverExpectedCondition::presenceOfAllElementsLocatedBy(WebDriverBy::id('adTagStatsTab'))
        );
//        $this->driver->wait()->until(
//            WebDriverExpectedCondition::invisibilityOfElementLocated(WebDriverBy::cssSelector('#adTagStatsTab > span'))
//        );

//		$this->driver->executeScript("window.scrollBy(0,250)", array());

        try {
            /** @var RemoteWebElement $downloadBtn */
            $this->driver->wait()->until(WebDriverExpectedCondition::presenceOfAllElementsLocatedBy(WebDriverBy::id('csv5')));
            $downloadElement = $this->driver->findElement(WebDriverBy::id('csv5'));

            $directoryStoreDownloadFile = $this->getDirectoryStoreDownloadFile($startDate, $endDate, $this->getConfig());
            $this->sleep(2);
            $this->downloadThenWaitUntilComplete($downloadElement, $directoryStoreDownloadFile);
            $this->logoutSystem();
        } catch (TimeOutException $te) {
            $this->logger->error('No data available for selected date range.');
        } catch (\Exception $exception) {
            $this->logger->error($exception->getMessage());
        }
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

    protected function logoutSystem()
    {
        $logOutAreaCss = '#headband > div.branding.clearfix > div.branding-container.clearfix > div.userWraper > div.username.clearfix > p';
        $this->driver->findElement(WebDriverBy::cssSelector($logOutAreaCss))->click();

        $logoutButtonCss = '//*[@id="userWrapper"]/div[2]/div/div[2]/a';
        $this->driver->wait()->until(
            WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::xpath($logoutButtonCss))
        );
        $this->driver->findElement(WebDriverBy::xpath($logoutButtonCss))->click();
    }
} 