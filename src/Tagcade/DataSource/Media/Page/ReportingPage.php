<?php

namespace Tagcade\DataSource\Media\Page;

use Facebook\WebDriver\Exception\NoSuchElementException;
use Facebook\WebDriver\Exception\TimeOutException;
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
            ->click()
        ;
        $this->driver->wait()->until(
            WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::id('reports'))
        );
        $this->driver->findElement(WebDriverBy::id('AdTags'))
            ->click()
        ;

        $this->driver->wait()->until(
            WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::cssSelector('#adTagStatsTab > span'))
        );
        $this->driver->wait()->until(
            WebDriverExpectedCondition::invisibilityOfElementLocated(WebDriverBy::cssSelector('#adTagStatsTab > span'))
        );

        $this->selectDateRange($startDate, $endDate);
        $this->driver->findElement(WebDriverBy::id('btnGo'))->click();

        $this->driver->wait()->until(
            WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::cssSelector('#adTagStatsTab > span'))
        );
        $this->driver->wait()->until(
            WebDriverExpectedCondition::invisibilityOfElementLocated(WebDriverBy::cssSelector('#adTagStatsTab > span'))
        );

        try {
            /** @var RemoteWebElement $downloadBtn */
            $downloadElement =  $this->driver->findElement(WebDriverBy::id('csv5'));
            $this->driver->wait()->until(WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::id('csv5')));

            $directoryStoreDownloadFile =  $this->getDirectoryStoreDownloadFile($startDate, $endDate, $this->getConfig());
            $this->downloadThenWaitUntilComplete($downloadElement, $directoryStoreDownloadFile);
        }
        catch (TimeOutException $te) {
            $this->logger->error('No data available for selected date range.');
        }
        catch (\Exception $exception) {
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
} 