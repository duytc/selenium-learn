<?php

namespace Tagcade\Service\Fetcher\Fetchers\OptimaticExternal\Page;

use DateTime;
use Exception;
use Facebook\WebDriver\Exception\InvalidSelectorException;
use Facebook\WebDriver\Exception\NoSuchElementException;
use Facebook\WebDriver\Exception\StaleElementReferenceException;
use Facebook\WebDriver\Remote\RemoteWebElement;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverElement;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Facebook\WebDriver\WebDriverSelect;
use Tagcade\Exception\RuntimeException;
use Tagcade\Service\Fetcher\Pages\AbstractPage;
use Tagcade\Service\Fetcher\Params\OptimaticExternal\OptimaticExternalPartnerParamsInterface;
use Tagcade\Service\Fetcher\Params\PartnerParamInterface;

class DeliveryReportPage extends AbstractPage
{
    const URL = 'https://publishers.optimatic.com/Portal2/';

    const REPORT_URL = 'https://publishers.optimatic.com/Portal2/reports/';

    const LOG_OUT_URL = 'https://publishers.optimatic.com/Portal2/Logout.aspx';

    /**
     * @param OptimaticExternalPartnerParamsInterface $params
     * @throws Exception
     */
    public function getAllTagReports(OptimaticExternalPartnerParamsInterface $params)
    {
        if (!$params instanceof PartnerParamInterface) {

        }

        if (!$params instanceof OptimaticExternalPartnerParamsInterface) {
            throw new Exception('must be optimatic external');
        }

        $config = $params->getConfig();
        $defaultDownloadPath = $config['defaultDownloadPath'];
        //step 0 redirect to report page
        $this->logger->debug('redirect to report page');
        $this->driver->navigate()->to(self::REPORT_URL);
        $this->logger->debug('Wait until appear select report type');
        $this->driver->wait()->until(WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::cssSelector('#menuContent > div.menu > div.filtersContainer > div.filtersHeader > div.dropdown > div.header')));

        //step 1 click slect report type and wait unitl has elements

        $this->driver->findElement(WebDriverBy::cssSelector('#menuContent > div.menu > div.filtersContainer > div.filtersHeader > div.dropdown > div.header'))->click();

        //step 2 click select report type
        // this part is divided to 2 case:  Today and orther report types
        // 2.1 if report type is Today
        $this->logger->debug('select report type');

        $reportTypeElement = $this->driver->findElement(WebDriverBy::cssSelector('#menuContent > div.menu > div.filtersContainer > div.filtersHeader > div.dropdown > div.container > div.content > div.list > div'));

        $optionElements = $reportTypeElement->findElements(WebDriverBy::tagName('div'));

        if ($params->getReportType() == 'Today') {
            //do something here for Today report type
            $this->logger->debug('Click report type');
            $optionElements[0]->click();

            //step 3 click select placements foreach placements
            $this->logger->debug('select Placements');
            $placementsElement = $this->driver->findElement(WebDriverBy::cssSelector('#menuContent > div.menu > div.filtersContainer > div.filtersHeader > div.siteSearchDropDown > div.container > div.content > div.list > div'));
            $placementsElement->click();
            $placementsOptionElements = $placementsElement->findElements(WebDriverBy::tagName('div'));

            $fileName = 'CombineToday';
            $dataToWrite[] = array();
            $i = 0;

            for ($j = 0; $j < count($placementsOptionElements); $j++) {

                $placementsElement = $this->driver->findElement(WebDriverBy::cssSelector('#menuContent > div.menu > div.filtersContainer > div.filtersHeader > div.siteSearchDropDown > div.container > div.content > div.list > div'));
                $placementsElement->click();

                $placementsOptionElement1s = $placementsElement->findElements(WebDriverBy::tagName('div'));
                $this->sleep(1);

                $placementText = $this->clickPlacement($placementsOptionElement1s[$j], count($placementsOptionElements) * 3);

                // click view report
                $this->logger->debug(sprintf('Click view report %s', $placementText));

                $this->driver->findElement(WebDriverBy::cssSelector('#menuContent > div.menu > div.filtersContainer > div.filtersContent > div > div.apply'))->click();
                // download report file Today.xls
                try {
                    $this->driver->wait()->until(
                        WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::cssSelector('#masterTable > div'))
                    );
                    $tableElement = $this->driver->findElement(WebDriverBy::cssSelector('#masterTable > div'));
                    $dataRows = $this->getDataFromTable($tableElement, $i);
                    $i++;

                    foreach ($dataRows as $dataRow) {
                        $dataToWrite[] = $dataRow;
                    }
                    $this->driver->findElement(WebDriverBy::cssSelector('#menuContent > div.menu > div.filtersContainer > div.filtersHeader > div.reportSearchDropDown > div.header'))->click();

                } catch (NoSuchElementException $e) {
                    $this->logger->warning(sprintf('Exception when get data exception message %s', $e->getMessage()));
                }

            }
            // combine to CombineToday.csv
            $this->saveToCSVFileFromTable($defaultDownloadPath, $fileName, $dataToWrite);

        } else {
            // do something for other report types
            $this->logger->debug('Click report type');
            foreach ($optionElements as $optionElement) {

                if ($params->getReportType() == $optionElement->getText()) {
                    $optionElement->click();
                    break;
                }

            }
            //step 3: select Date Range

            $this->logger->debug('select Date Range');

            $this->driver->findElement(WebDriverBy::cssSelector('div.container.divDateRangeSelector > div > div.customDate'))->click();

            $this->logger->debug('Wait until appear Start and End date');
            $this->driver->wait()->until(WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::cssSelector('body > div.rangeSelector > div.dateRange > div.calendars')));
            /*
             * select date
             */
            $this->setDateRange($params->getStartDate(), $params->getEndDate());
            // click ok to finish select date range process
            $this->logger->debug('click ok to finish select date range process');
            $this->driver->findElement(WebDriverBy::cssSelector("body > div.rangeSelector > div.dateRange > div.footer > div.apply"))->click();

            //step 4 click select placements foreach placements
            $this->logger->debug('select Placements');
            $placementsElement = $this->driver->findElement(WebDriverBy::cssSelector('#menuContent > div.menu > div.filtersContainer > div.filtersHeader > div.siteSearchDropDown > div.container > div.content > div.list > div'));
            $placementsElement->click();
            $placementsOptionElements = $placementsElement->findElements(WebDriverBy::tagName('div'));

            $fileName = 'Combine' . $params->getReportType();
            $dataToWrite[] = array();
            $i = 0;
            for ($j = 0; $j < count($placementsOptionElements); $j++) {

                $placementsElement = $this->driver->findElement(WebDriverBy::cssSelector('#menuContent > div.menu > div.filtersContainer > div.filtersHeader > div.siteSearchDropDown > div.container > div.content > div.list > div'));
                $placementsElement->click();

                $placementsOptionElement1s = $placementsElement->findElements(WebDriverBy::tagName('div'));
                $this->sleep(1);

                $placementText = $this->clickPlacement($placementsOptionElement1s[$j], count($placementsOptionElements));

                // click view report
                $this->logger->debug(sprintf('Click view report %s', $placementText));

                $this->driver->findElement(WebDriverBy::cssSelector('#menuContent > div.menu > div.filtersContainer > div.filtersContent > div > div.apply'))->click();
                // download report file
                try {
                    $this->driver->wait()->until(
                        WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::cssSelector('body > div.mainContainer > div > div.tableDownload'))
                    );
                    $tableElement = $this->driver->findElement(WebDriverBy::cssSelector('body > div.mainContainer > div > div.tableDownload > div.table'));
                    $dataRows = $this->getDataFromTableAnotherReports($tableElement, $i, $placementText, $params->getReportType());
                    $i++;

                    foreach ($dataRows as $dataRow) {
                        $dataToWrite[] = $dataRow;
                    }
                    $this->driver->findElement(WebDriverBy::cssSelector('#menuContent > div.menu > div.filtersContainer > div.filtersHeader > div.reportSearchDropDown > div.header'))->click();

                } catch (NoSuchElementException $e) {
                    $this->logger->warning(sprintf('Exception when get data exception message %s', $e->getMessage()));
                }

            }
            // combine to CombineReportType.csv
            $this->saveToCSVFileFromTable($defaultDownloadPath, $fileName, $dataToWrite);

        }

        $this->sleep(2);

    }

    /**
     * @param $defaultDownloadPath
     * @param string $fileName
     * @param $dataToWrite
     * @throws Exception
     * @internal param DateTime $startDate
     * @internal param DateTime $endDate
     * @internal param RemoteWebElement $tableElement
     * @internal param $path
     */
    public function saveToCSVFileFromTable($defaultDownloadPath, $fileName, $dataToWrite)
    {
        if (!is_dir($defaultDownloadPath)) {
            mkdir($defaultDownloadPath, 0777, true);
        }

        $path = $csvFilePath = sprintf('%s/%s.csv', $defaultDownloadPath, $fileName);

        if (is_dir($path)) {
            $this->logger->warning(sprintf('The path is not file, path is %s', $path));
            throw new Exception ('Path must be file');
        }

        $this->arrayToCSVFile($path, $dataToWrite);
    }

    /**
     * @param RemoteWebElement $tableElement
     * @param $i
     * @return array
     * @throws InvalidSelectorException
     */
    public function getDataFromTable(RemoteWebElement $tableElement, $i)
    {

        if (!$tableElement instanceof RemoteWebElement) {
            $this->logger->warning('Invalid remove web element');
            throw new InvalidSelectorException('Invalid remove web element');
        }
        //if i>0 don't download the first row contain title
        $dataRows = [];
        $oneRows = [];

        $this->driver->wait()->until(WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::cssSelector('#masterTable > div')));
        if ($i == 0) {

            $tableElement1 = $this->driver->findElement(WebDriverBy::cssSelector('#masterTable > div > div.slick-header > div'));
            $rowElement1s = $tableElement1->findElements(WebDriverBy::tagName('div'));
            $this->logger->debug('Get data from table element');

            try {

                foreach ($rowElement1s as $rowElement1) {
                    if (!empty($rowElement1->getText()))
                        $oneRows[] = $rowElement1->getText();
                }
                //delete null value
                if (is_array($oneRows) && !empty($oneRows)) {

                    $dataRows[] = $oneRows;
                    $oneRows = null;
                }


            } catch (StaleElementReferenceException $e) {
                $this->logger->warning($e->getMessage());
            }

        }

        $this->logger->debug('Find table element');
        $tableElement = $this->driver->findElement(WebDriverBy::cssSelector('#masterTable > div > div.slick-viewport > div'));
        $rowElements = $tableElement->findElements(WebDriverBy::tagName('div'));
        $this->logger->debug('Get data from table element');

        //   foreach ($rowElements as $rowElement) {
        $tdElements = $rowElements[0]->findElements(WebDriverBy::cssSelector('div'));
        $this->sleep(2);
        try {
            /** @var RemoteWebElement $tdElement */
            foreach ($tdElements as $tdElement) {
                $oneRows[] = $tdElement->getText();
            }
            if (is_array($oneRows) && !empty($oneRows)) {

                $dataRows[] = $oneRows;
                $oneRows = null;
            }

        } catch (StaleElementReferenceException $e) {
            $this->logger->warning($e->getMessage());
        }
        //  }

        return $dataRows;
    }

    /**
     * @param RemoteWebElement $tableElement
     * @param $i
     * @param $placementText
     * @param $reportTypeText
     * @return array
     * @throws InvalidSelectorException
     */
    public function getDataFromTableAnotherReports(RemoteWebElement $tableElement, $i, $placementText, $reportTypeText)
    {

        if (!$tableElement instanceof RemoteWebElement) {
            $this->logger->warning('Invalid remove web element');
            throw new InvalidSelectorException('Invalid remove web element');
        }
        //if i>0 don't download the first row contain title
        $dataRows = [];
        $oneRows = [];

        $this->driver->wait()->until(WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::cssSelector('body > div.mainContainer > div > div.tableDownload > div.table')));
        if ($i == 0) {

            $tableElement1 = $this->driver->findElement(WebDriverBy::cssSelector('body > div.mainContainer > div > div.tableDownload > div.table > div.slick-header> div'));
            $rowElement1s = $tableElement1->findElements(WebDriverBy::tagName('div'));
            $this->logger->debug('Get Title from table element');

            try {

                foreach ($rowElement1s as $rowElement1) {
                    if (!empty($rowElement1->getText()))
                        $oneRows[] = $rowElement1->getText();
                }
                if ($reportTypeText != 'Revenue By Placement')
                    array_unshift($oneRows, "PLACEMENTS");
                //delete null value
                if (is_array($oneRows) && !empty($oneRows)) {

                    $dataRows[] = $oneRows;
                    $oneRows = null;
                }


            } catch (StaleElementReferenceException $e) {
                $this->logger->warning($e->getMessage());
            }

        }
        $this->logger->debug('Find table element');
        $tableElement = $this->driver->findElement(WebDriverBy::cssSelector('body > div.mainContainer > div > div.tableDownload > div.table> div.slick-viewport > div'));
//        $rowElements = $tableElement->findElements(WebDriverBy::className('even'));
//        $rowElements = array_merge($rowElements, $tableElement->findElements(WebDriverBy::className('odd')));
        $rowElements = $tableElement->findElements(WebDriverBy::className('ui-widget-content'));

        $this->logger->debug('Get data from table element');
        $totalText = false;
        foreach ($rowElements as $rowElement) {
            $tdElements = $rowElement->findElements(WebDriverBy::cssSelector('div'));

            if (is_array($tdElements) && !empty($tdElements)) {
                $this->sleep(1);
                try {
                    /** @var RemoteWebElement $tdElement */
                    foreach ($tdElements as $tdElement) {
                        if ($tdElement->getText() == "Total") {
                            $totalText = true;
                            break;
                        }
                        $oneRows[] = $tdElement->getText();
                    }

                    if (is_array($oneRows) && !empty($oneRows)) {
                        if ($reportTypeText != 'Revenue By Placement')
                            array_unshift($oneRows, $placementText);
                        $dataRows[] = $oneRows;
                        $oneRows = null;
                    }

                } catch (StaleElementReferenceException $e) {
                    $this->logger->warning($e->getMessage());
                }
            }

            if ($totalText == true) break;
        }

        return $dataRows;
    }

    /**
     * @param DateTime $startDate
     * @param DateTime $endDate
     * @return $this
     */
    public function setDateRange(DateTime $startDate, DateTime $endDate)
    {
        $this->setStartDate($startDate);
        $this->setEndDate($endDate);

        return $this;
    }

    /**
     * @param DateTime $startDate
     */
    protected function setStartDate(DateTime $startDate)
    {
        $startDateMonth = $startDate->format("M");
        $startDateDay = $startDate->format("j");
        $startDateYear = $startDate->format("Y");

        $yearElement = $this->driver->findElement(WebDriverBy::cssSelector('body > div.rangeSelector > div.dateRange > div.calendars > div.startCalendar > div > div > div > select.ui-datepicker-year'))->click();
        $yearSelect = new WebDriverSelect($yearElement);
        $yearSelect->selectByValue($startDateYear);

        $startMonth = $this->driver->findElement(WebDriverBy::cssSelector('body > div.rangeSelector > div.dateRange > div.calendars > div.startCalendar > div > div > div > select.ui-datepicker-month'))->click();

        $months = $startMonth->findElements(WebDriverBy::tagName('option'));
        foreach ($months as $month) {

            if ($startDateMonth == $month->getText()) {
                $month->click();
                break;
            }
        }
        $startDay = $this->driver->findElement(WebDriverBy::cssSelector('body > div.rangeSelector > div.dateRange > div.calendars > div.startCalendar > div > table > tbody'));
        try {
            $trs = $startDay->findElements(WebDriverBy::tagName('tr'));
        } catch (\Exception $e) {
            $this->logger->debug('Exception get tr element' .$e);
        }

        $checkDay = false;
        foreach ($trs as $tr) {
            $tds = $tr->findElements(WebDriverBy::tagName('td'));
            foreach ($tds as $td) {

                if ($startDateDay == $td->getText()) {
                    $td->click();
                    $checkDay = true;
                    break;
                }
            }
            if ($checkDay == true) {
                break;
            }
        }
    }

    /**
     * @param DateTime $endDate
     */
    protected function setEndDate(DateTime $endDate)
    {
        $endDateMonth = $endDate->format("M");
        $endDateDay = $endDate->format("j");
        $endDateYear = $endDate->format("Y");

        $yearElement = $this->driver->findElement(WebDriverBy::cssSelector('body > div.rangeSelector > div.dateRange > div.calendars > div.endCalendar > div > div > div > select.ui-datepicker-year'))->click();
        $yearSelect = new WebDriverSelect($yearElement);
        $yearSelect->selectByValue($endDateYear);

        $startMonth = $this->driver->findElement(WebDriverBy::cssSelector('body > div.rangeSelector > div.dateRange > div.calendars > div.endCalendar > div > div > div > select.ui-datepicker-month'))->click();
        $months = $startMonth->findElements(WebDriverBy::tagName('option'));
        foreach ($months as $month) {

            if ($endDateMonth == $month->getText()) {
                $month->click();
                break;
            }
        }
        $startDay = $this->driver->findElement(WebDriverBy::cssSelector('body > div.rangeSelector > div.dateRange > div.calendars > div.endCalendar > div > table > tbody'));
        $trs = $startDay->findElements(WebDriverBy::tagName('tr'));
        $checkDay = false;
        foreach ($trs as $tr) {
            $tds = $tr->findElements(WebDriverBy::tagName('td'));
            foreach ($tds as $td) {

                if ($endDateDay == $td->getText()) {
                    $td->click();
                    $checkDay = true;
                    break;
                }
            }
            if ($checkDay == true) {
                break;
            }
        }
    }

    /**
     * @param $placement
     * @param $totalPlacements
     * @return string
     */
    protected function clickPlacement(WebDriverElement $placement, $totalPlacements)
    {
        $retry = 0;
        $isDone = false;
        do {
            try {
                $retry++;

                $placement->click();

                // click placement
                $this->logger->debug(sprintf('Click Placements %s', $placement->getText()));
                $isDone = true;
            } catch (\Exception $e) {
                if ($retry > $totalPlacements) {
                    throw new RuntimeException('Runtime when try to click placements.');
                }
                $this->logger->debug(sprintf('windows scroll the %s times', $retry));

                $this->driver->executeScript("window.scrollBy(0,117)", array());
                $this->driver->action()->moveToElement($placement)->perform();
            }
        } while (!$isDone);

        return $placement->getText();
    }
}