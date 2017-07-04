<?php

namespace Tagcade\Service\Fetcher\Fetchers\OptimaticInternalSupply\Page;

use DateTime;
use Exception;
use Facebook\WebDriver\Exception\InvalidSelectorException;
use Facebook\WebDriver\Exception\NoSuchElementException;
use Facebook\WebDriver\Exception\StaleElementReferenceException;
use Facebook\WebDriver\Remote\RemoteWebElement;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Facebook\WebDriver\WebDriverKeys;
use Facebook\WebDriver\WebDriverSelect;
use PHPExcel;
use PHPExcel_IOFactory;
use Tagcade\Service\Fetcher\Pages\AbstractPage;
use Tagcade\Service\Fetcher\Params\OptimaticInternalSupply\OptimaticInternalSupplyPartnerParamsInterface;
use Tagcade\Service\Fetcher\Params\PartnerParamInterface;

class DeliveryReportPage extends AbstractPage
{
    const URL = 'https://publishers.optimatic.com/Portal2/';

    const REPORT_URL = 'https://publishers.optimatic.com/Portal2/reports/';

    const LOG_OUT_URL = 'https://publishers.optimatic.com/Portal2/Logout.aspx';

    /**
     * @param OptimaticInternalSupplyPartnerParamsInterface $params
     * @throws Exception
     */
    public function getAllTagReports(OptimaticInternalSupplyPartnerParamsInterface $params)
    {
        if (!$params instanceof PartnerParamInterface) {

        }

        if (!$params instanceof OptimaticInternalSupplyPartnerParamsInterface) {
            throw new Exception('must be optimatic internal supply');
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
            $this->logger->debug('select all placements');
            $placementsElement = $this->driver->findElement(WebDriverBy::cssSelector('#menuContent > div.menu > div.filtersContainer > div.filtersHeader > div.siteSearchDropDown > div.container > div.selectAll'));
            $placementsElement->click();

            $fileName = 'CombineToday';
            $dataToWrite = '';
            $this->sleep(1);

            // click view report
            $this->logger->debug('Click view report');
            $this->driver->findElement(WebDriverBy::cssSelector('#menuContent > div.menu > div.filtersContainer > div.filtersContent > div > div.apply'))->click();
            // download report file Today.xls
            try {
                $this->driver->wait()->until(
                    WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::cssSelector('#masterTable > div'))
                );
                $tableElement = $this->driver->findElement(WebDriverBy::cssSelector('#masterTable > div'));
                $dataRows = $this->getDataFromTable($tableElement);

                foreach ($dataRows as $dataRow) {
                    $dataToWrite[] = $dataRow;
                }

            } catch (NoSuchElementException $e) {
                $this->logger->warning(sprintf('Exception when get data exception message %s', $e->getMessage()));
            }

            // combine to CombineToday.csv
            $this->saveToCSVFileFromTable($defaultDownloadPath, $fileName, $dataToWrite);

        } else {
            // do something for other report types
            $this->logger->debug('Click report type');
            foreach ($optionElements as $optionElement) {

                if ($params->getReportType() == $optionElement->getText()) {
                    $optionElement->click();
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
            $this->logger->debug('select all placements');
            $placementsElement = $this->driver->findElement(WebDriverBy::cssSelector('#menuContent > div.menu > div.filtersContainer > div.filtersHeader > div.siteSearchDropDown > div.container > div.selectAll'));
            $placementsElement->click();
            $this->sleep(1);

            // click view report, use action click and hold 1 second
            $viewReportElement = $this->driver->findElement(WebDriverBy::cssSelector('#menuContent > div.menu > div.filtersContainer > div.filtersContent > div > div.apply'));
            $this->driver->action()->clickAndHold($viewReportElement)->perform();
            $this->sleep(1);
            $this->logger->debug('click and hold view report 1 second to make sure that the click action is right');
            $this->driver->action()->release()->perform();
            try {
                $this->driver->wait()->until(
                    WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::cssSelector('body > div.mainContainer > div > div.tableDownload'))
                );
                // download report file
                $this->driver->findElement(WebDriverBy::cssSelector('body > div.mainContainer > div > div.tableDownload > div.download'))->click();

            } catch (NoSuchElementException $e) {
                $this->logger->warning(sprintf('Exception when get data exception message %s', $e->getMessage()));
            }


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

        $path = $csvFilePath = sprintf('%s/%s.xls', $defaultDownloadPath, $fileName);

        if (is_dir($path)) {
            $this->logger->warning(sprintf('The path is not file, path is %s', $path));
            throw new Exception ('Path must be file');
        }

        // Create new PHPExcel object
        $phpExcelObject = new PHPExcel();

        $phpExcelObject->getProperties()->setCreator("Today")
            ->setTitle('Phuongdinh')
            ->setSubject('PhuongDinh');

        $sheet = $phpExcelObject->setActiveSheetIndex(0);

        $sheet->setCellValue('A1', 'Today');

        $counter = 2;
        foreach ($dataToWrite as $data) {
            if (count($data) < 1) break;
            $sheet->setCellValue('A' . $counter, $data[0]);
            $sheet->setCellValue('B' . $counter, $data[1]);
            $sheet->setCellValue('C' . $counter, $data[2]);
            $sheet->setCellValue('D' . $counter, $data[3]);
            $sheet->setCellValue('E' . $counter, $data[4]);
            $sheet->setCellValue('F' . $counter, $data[5]);
            $sheet->setCellValue('G' . $counter, $data[6]);
            $sheet->setCellValue('H' . $counter, $data[7]);
            $counter++;
        }

        $phpExcelObject->getActiveSheet()->setTitle('Title');

        // Set active sheet index to the first sheet, so Excel opens this as the first sheet
        $phpExcelObject->setActiveSheetIndex(0);

        $objWriter = PHPExcel_IOFactory::createWriter($phpExcelObject, 'Excel5');
        $objWriter->save($path);

    }

    /**
     * @param RemoteWebElement $tableElement
     * @return array
     * @throws InvalidSelectorException
     */
    public function getDataFromTable(RemoteWebElement $tableElement)
    {

        if (!$tableElement instanceof RemoteWebElement) {
            $this->logger->warning('Invalid remove web element');
            throw new InvalidSelectorException('Invalid remove web element');
        }
        //if i>0 don't download the first row contain title
        $dataRows = [];
        $oneRows = [];

        $this->driver->wait()->until(WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::cssSelector('#masterTable > div')));

        $tableElement1 = $this->driver->findElement(WebDriverBy::cssSelector('#masterTable > div > div.slick-header > div'));

        $rowElement1s = $tableElement1->findElements(WebDriverBy::tagName('div'));
        $this->logger->debug('Get Title data from table element');

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

        $this->logger->debug('Find table element');
        $tableElement = $this->driver->findElement(WebDriverBy::cssSelector('#masterTable > div > div.slick-viewport > div'));
        $sliceElement = $this->driver->findElement(WebDriverBy::cssSelector('#masterTable > div > div.slick-viewport'));

        //$rowElements =  $tableElement->findElements(WebDriverBy::xpath("//div[@attribute='row']"));
        //$count = count($rowElements);
        $this->logger->debug('Get data from table element');
        $last_row = 0;
        $isDone = false;
        do {
            try {
                $this->logger->debug('step1');
                $rowElements = $tableElement->findElements(WebDriverBy::className('ui-widget-content'));
                $totalText = false;
                $this->logger->debug('step2');

                if ($last_row < $rowElements[count($rowElements) - 1]->getAttribute('row')) {

                    $this->logger->debug('step3');
                    foreach ($rowElements as $rowElement) {
                        $row = $rowElement->getAttribute('row');
                        $this->logger->debug('Row' . $row);
                        if ( $row == 0 || $row > $last_row) {
                            $tdElements = $rowElement->findElements(WebDriverBy::cssSelector('div'));
                            if (is_array($tdElements) && !empty($tdElements)) {
                             //   $this->sleep(1);
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
                                        $dataRows[] = $oneRows;
                                        $oneRows = null;
                                    }

                                } catch (StaleElementReferenceException $e) {
                                    $this->logger->warning($e->getMessage());
                                }
                            }

                            if ($totalText == true) {
                                $isDone = true;
                                break;
                            }

                        }

                    }

                    $last_row = $row;
                    $this->logger->debug('last row' . $last_row);
                    /** Each page down pressed, row increase 12*/
                    for($i = 0; $i < 2; $i++) {
                        $sliceElement->sendKeys(WebDriverKeys::PAGE_DOWN);
                    }
                } elseif ($last_row > $rowElements[count($rowElements) - 1]->getAttribute('row')) {
                    /** Each page down pressed, row increase 12*/
                        $sliceElement->sendKeys(WebDriverKeys::PAGE_DOWN);
                } elseif ($last_row < $rowElements[0]->getAttribute('row')) {
                    $sliceElement->sendKeys(WebDriverKeys::PAGE_UP);
                }

            } catch (StaleElementReferenceException $e) {
                $this->logger->debug('Get Log' . $e);
            }
        } while (!$isDone);

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
        $this->logger->debug('Select Start Year');
        $yearElement = $this->driver->findElement(WebDriverBy::cssSelector('body > div.rangeSelector > div.dateRange > div.calendars > div.startCalendar > div > div > div > select.ui-datepicker-year'))->click();
        $yearSelect = new WebDriverSelect($yearElement);
        $yearSelect->selectByValue($startDateYear);

        $this->logger->debug('Select Start Month');
        $startMonth = $this->driver->findElement(WebDriverBy::cssSelector('body > div.rangeSelector > div.dateRange > div.calendars > div.startCalendar > div > div > div > select.ui-datepicker-month'))->click();
        $months = $startMonth->findElements(WebDriverBy::tagName('option'));
        foreach ($months as $month) {

            if ($startDateMonth == $month->getText()) {
                $month->click();
                break;
            }
        }
        $this->logger->debug('Select Start Date');
        $startDay = $this->driver->findElement(WebDriverBy::cssSelector('body > div.rangeSelector > div.dateRange > div.calendars > div.startCalendar > div > table > tbody'));
        try {
            $trs = $startDay->findElements(WebDriverBy::tagName('tr'));
        } catch (\Exception $e) {
            $this->logger->debug('Exception get tr element' . $e);
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

        $this->logger->debug('Select End Year');
        $yearElement = $this->driver->findElement(WebDriverBy::cssSelector('body > div.rangeSelector > div.dateRange > div.calendars > div.endCalendar > div > div > div > select.ui-datepicker-year'))->click();
        $yearSelect = new WebDriverSelect($yearElement);
        $yearSelect->selectByValue($endDateYear);

        $this->logger->debug('Select End Month');
        $endMonth = $this->driver->findElement(WebDriverBy::cssSelector('body > div.rangeSelector > div.dateRange > div.calendars > div.endCalendar > div > div > div > select.ui-datepicker-month'))->click();
        $months = $endMonth->findElements(WebDriverBy::tagName('option'));
        foreach ($months as $month) {

            if ($endDateMonth == $month->getText()) {
                $month->click();
                break;
            }
        }
        $this->logger->debug('Select End Date');
        $endDay = $this->driver->findElement(WebDriverBy::cssSelector('body > div.rangeSelector > div.dateRange > div.calendars > div.endCalendar > div > table > tbody'));

        //$trs = $endDay->findElements(WebDriverBy::tagName('tr'));
        try {
            $trs = $endDay->findElements(WebDriverBy::tagName('tr'));
        } catch (\Exception $e) {
            $this->logger->debug('Exception get tr element' . $e);
        }
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
}