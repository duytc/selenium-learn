<?php


namespace Tagcade\DataSource\NativeAds\Page;


use Facebook\WebDriver\Exception\InvalidSelectorException;
use Facebook\WebDriver\Exception\StaleElementReferenceException;
use Facebook\WebDriver\Exception\TimeOutException;
use Facebook\WebDriver\Remote\RemoteWebElement;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Symfony\Component\Validator\Constraints\DateTime;
use Tagcade\DataSource\NativeAds\Widget\DateSelectWidget;
use Tagcade\DataSource\PulsePoint\Page\AbstractPage;

class ReportingPage extends AbstractPage {

    const URL = 'https://nativeads.com/publisher/reports-widget.php';
    const REPORT_FILE_NAME = 'report';

    /**
     * @param \DateTime $startDate
     * @param \DateTime $endDate
     * @throws \Exception
     * @throws \Facebook\WebDriver\Exception\NoSuchElementException
     * @throws \Facebook\WebDriver\Exception\TimeOutException
     * @throws null
     */
    public function getAllTagReports(\DateTime $startDate, \DateTime $endDate)
    {
        $reportDisplay= $this->driver->findElement(WebDriverBy::cssSelector('a[href="reports-widget.php"]'))->isDisplayed();

        if( false == $reportDisplay ) {
            $this->driver->findElement(WebDriverBy::cssSelector('a[href="#"]'))
                ->click();

            $this->driver->wait()->until(
                WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::cssSelector('a[href="reports-widget.php"]'))
            );

            $this->driver->findElement(WebDriverBy::cssSelector('a[href="reports-widget.php"]'))
                ->click()
            ;

            $this->logger->debug('Wait loading report in 5s');
            $this->driver->manage()->timeouts()->setScriptTimeout(10);
        }

        $this->logger->debug('Getting header and data for range days ');
        $tableElement = $this->driver->findElement(WebDriverBy::id('datatable_tabletools'));

        $headerData = $this->getHeaderFromTable($tableElement);
        $rangeDaysDatas = $this->getDataForRangeDays(clone $startDate, clone $endDate);

        $dataToWrite = $this->createDataToWrite($headerData, $rangeDaysDatas);

        $this->logger->debug('Write data to file');
        $path = $this->getPath($startDate, $endDate, $this->getConfig(), self::REPORT_FILE_NAME);
        $this->arrayToCSVFile($path, $dataToWrite);
    }

    /**
     * @param $headerData
     * @param $rangeDaysDatas
     * @return array
     */
    public function createDataToWrite ($headerData, $rangeDaysDatas)
    {
        $dataToWrite = [];
        $dataToWrite[] = $headerData;

        foreach($rangeDaysDatas as $rangeDaysData) {
            foreach($rangeDaysData as $adTagData) {
                $dataToWrite[]=$adTagData;
            }
        }

        return $dataToWrite;
    }

    /**
     * @param \DateTime $startDate
     * @param \DateTime $endDate
     * @return array
     * @throws InvalidSelectorException
     */
    public function getDataForRangeDays (\DateTime $startDate, \DateTime $endDate)
    {
        $interval = $startDate->diff($endDate);
        $this->logger->debug(sprintf('Number of days: %s',$interval->days));
        $dateInterval = new \DateInterval('P1D'); // 1 day
        $startDate = $startDate->sub($dateInterval);

        $allData =[];

        for ($date = 0; $date <= $interval->days ; $date++)
        {
            $dateReport = $startDate->add($dateInterval);
            $this->logger->debug(sprintf('Date to save data %s', $dateReport->format('Y-m-d')));

            $dateToWrite[] = array($dateReport->format('Y-m-d'));

            $this->setDownloadDate($dateReport);

            $this->logger->debug(sprintf('Set date %s finish', $dateReport->format('Y-m-d')));

            $tableElement = $this->driver->findElement(WebDriverBy::id('datatable_tabletools'));
            $rows = $this->getDataFromTable($tableElement);

            $allData[] = $dateToWrite;
            $dateToWrite = null;
            $allData[] = $rows;

            $this->logger->debug(sprintf('Get data from table finish'));
        }

        return $allData;
    }

    /**
     * @param RemoteWebElement $tableElement
     * @return array
     * @throws InvalidSelectorException
     * @throws TimeOutException
     * @throws \Exception
     * @throws \Facebook\WebDriver\Exception\NoSuchElementException
     * @throws null
     */
    public function getHeaderFromTable (RemoteWebElement $tableElement)
    {
        if(!$tableElement instanceof RemoteWebElement) {
            throw new InvalidSelectorException('Invalid remove web element');
        }

        $oneRows =[];
        $this->logger->debug('Find table element');
        /** @var RemoteWebElement $tableRow */
        $this->driver->wait()->until(WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::id('datatable_tabletools')));

        $this->logger->debug('Get header data from table');
        $tdElements = $this->driver->findElements(WebDriverBy::xpath('//*[@id="datatable_tabletools"]/thead/tr/th'));

        /** @var RemoteWebElement $tdElement */
        foreach ($tdElements as $tdElement) {
            $oneRows[] = $tdElement->getText();
        }

        return $oneRows;
    }

    /**
     * @param RemoteWebElement $tableElement
     * @return array
     * @throws InvalidSelectorException
     */
    public function getDataFromTable (RemoteWebElement $tableElement) {
        $dataRows =[];
        $oneRows =[];

        if(!$tableElement instanceof RemoteWebElement) {
            throw new InvalidSelectorException('Invalid remove web element');
        }

        $this->logger->debug('Find table element');
        /** @var RemoteWebElement $tableRow */
        $this->driver->wait()->until(WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::id('datatable_tabletools')));
        $rowElements = $tableElement->findElements(WebDriverBy::xpath('//*[@id="datatable_tabletools"]/tbody/tr'));

        $this->logger->debug('Get data from table element');
        foreach ($rowElements as $rowElement) {
            $tdElements = $rowElement->findElements(WebDriverBy::cssSelector('td'));
            try {
                /** @var RemoteWebElement $tdElement */
                foreach ($tdElements as $tdElement) {
                    $oneRows[] = $tdElement->getText();
                }
                $dataRows[] = $oneRows;
                $oneRows =null;
            } catch (StaleElementReferenceException $e) {
                $this->logger->warning($e->getMessage());
            }
        }

        return $dataRows;
    }

    /**
     * @param \DateTime $downloadDate
     * @return $this
     */
    protected function setDownloadDate(\DateTime $downloadDate)
    {
        $dateWidget = new DateSelectWidget($this->driver);
        $dateWidget->setDate($downloadDate);

        return $this;
    }
} 