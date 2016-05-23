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
        // select filter by ad units
        $this->driver->findElement(WebDriverBy::id('AdTags'))
            ->click()
        ;

        $this->driver->wait()->until(
            WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::id('csv5'))
        );

        // Step 1. Select date range
        $this->selectDateRange($startDate, $endDate);

        // select filter by ad units
        $this->driver->findElement(WebDriverBy::id('btnGo'))
            ->click()
        ;

        try {

            /** @var RemoteWebElement $downloadBtn */
            $downloadBtn =  $this->driver->findElement(WebDriverBy::id('csv5'));

            $this->driver->wait()->until(WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::id('csv5')));

            $this->downloadThenWaitUntilComplete($downloadBtn);
        }
        catch (TimeOutException $te) {
            $this->logger->error('Not data to download');
        }
        catch (\Exception $exception) {
            $this->logger->error($exception->getMessage());
        }

    }

    protected function selectDateRange(\DateTime $startDate, \DateTime $endDate)
    {
        $dateWidget = new DateSelectWidget($this->driver);
        $dateWidget->setDateRange($startDate, $endDate);
        return $this;
    }
} 