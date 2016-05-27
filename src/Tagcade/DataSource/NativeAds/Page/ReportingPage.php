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

            $this->logger->info('Wait loading report in 5s');
            $this->driver->manage()->timeouts()->setScriptTimeout(10);
        }

        $path = $this->getPath($startDate, $endDate, $this->getConfig(), self::REPORT_FILE_NAME);
        $dataToWrite = [];

        $this->logger->info('Getting header of element');
        $tableElement = $this->driver->findElement(WebDriverBy::id('datatable_tabletools'));

        $headerData = $this->getHeaderFromTable($tableElement);
        $dataToWrite[] = $headerData;

        $this->logger->info('Getting table data for range days ');
        $rangeDaysDatas = $this->getDataForRangeDays($startDate, $endDate);

        foreach($rangeDaysDatas as $rangeDaysData) {

            foreach($rangeDaysData as $adTagData) {
                $dataToWrite[]=$adTagData;
            }
        }

        $this->logger->info('Write data to file');
        $this->arrayToCSVFile($path,$dataToWrite);
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
        $this->logger->info(sprintf('Number of days: %s',$interval->days));
        $dateInterval = new \DateInterval('P1D'); // 1 day
        $startDate = $startDate->sub($dateInterval);

        $allData =[];

        for ($date = 0; $date <= $interval->days ; $date++)
        {
            $dateReport = $startDate->add($dateInterval);
            $this->logger->info(sprintf('Date to save data %s', $dateReport->format('y-m-d')));

            $dateToWrite[] = array($dateReport->format('y-m-d'));

            $this->setDownloadDate($dateReport);

            $this->logger->info(sprintf('Set date %s finish', $dateReport->format('y-m-d')));

            $tableElement = $this->driver->findElement(WebDriverBy::id('datatable_tabletools'));
            $rows = $this->getDataFromTable($tableElement);

            $allData[] = $dateToWrite;
            $dateToWrite = null;
            $allData[] = $rows;

            $this->logger->info(sprintf('Get data from table finish'));

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
        $this->logger->info('Find table element');
        /** @var RemoteWebElement $tableRow */
        $this->driver->wait()->until(WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::id('datatable_tabletools')));

        $this->logger->info('Get header data from table');
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
        if(!$tableElement instanceof RemoteWebElement) {
            throw new InvalidSelectorException('Invalid remove web element');
        }
        $dataRows =[];
        $oneRows =[];
        $this->logger->info('Find table element');
        /** @var RemoteWebElement $tableRow */
        $this->driver->wait()->until(WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::id('datatable_tabletools')));
        $rowElements = $tableElement->findElements(WebDriverBy::xpath('//*[@id="datatable_tabletools"]/tbody/tr'));
        $this->logger->info('Get data from table element');
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
                $this->logger->info($e->getMessage());
            }
        }

        return $dataRows;
    }

    /**
     * Get path to store csv file
     * @param \DateTime $startDate
     * @param \DateTime $endDate
     * @return string
     */
    protected  function getPath(\DateTime $startDate, \DateTime $endDate, $config ,$fileName)
    {

        $rootDirectory = $this->downloadFileHelper->getRootDirectory();
        $publisherId = $config['publisher_id'];
        $partnerCName = $config['partner_cname'];

        $publisherPath = sprintf('%s/%s', realpath($rootDirectory), $publisherId);
        if (!is_dir($publisherPath)) {
            mkdir($publisherPath);
        }

        $partnerPath = $tmpPath = sprintf('%s/%s', $publisherPath, $partnerCName);
        if (!is_dir($partnerPath)) {
            mkdir($partnerPath);
        }

        $directory = sprintf('%s/%s-%s', $partnerPath , $startDate->format('ymd'), $endDate->format('ymd'));
        var_dump($directory);
        if (!is_dir($directory)) {
            mkdir($directory);
        }

        $path = sprintf('%s/%s.csv', $directory, $fileName);
        if (file_exists($path)) {
            unlink($path);
        }

        return $path;
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