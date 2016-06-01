<?php

namespace Tagcade\DataSource\CpmBase\Page;

use DateTime;
use Facebook\WebDriver\Exception\InvalidSelectorException;
use Facebook\WebDriver\Exception\NoSuchElementException;
use Facebook\WebDriver\Exception\StaleElementReferenceException;
use Facebook\WebDriver\Exception\TimeOutException;
use Facebook\WebDriver\Remote\RemoteWebElement;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverElement;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Facebook\WebDriver\WebDriverSelect;
use Facebook\WebDriver\WebDriverWait;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Validator\Constraints\Date;
use Tagcade\DataSource\CpmBase\Widget\DateSelectWidget;
use Tagcade\DataSource\PulsePoint\Page\AbstractPage;

class ReportingPage extends AbstractPage  {

    const URL = 'http://publisher.cpmbase.com/reporting';

    const GROUP_BY_SIZE_VALUE ='size';
    const GRANULARITY_DAY_VALUE = 'day';
    const PARTNER_CNAME_VALUE = 'CpmBase';

    public function getAllTagReports(\DateTime $startDate, \DateTime $endDate)
    {
        $this->driver->findElement(WebDriverBy::cssSelector('a[href="/reporting"]'))->click();

        $this->selectGranularity();
        $this->selectDateRange($startDate, $endDate);
        $this->selectGroupBy();

        // Wait for default site report loaded
        $this->waitForJquery();
        $this->info('Site has Jquery present');
        sleep(2);
        $this->info('Waiting for overlay');
        $this->waitForOverlay('#fancybox-loading');

        $this->getAllTagReportsForMultiSites($startDate, $endDate);
    }

    /**
     * Get all ad tags reports for single domain (publisher)
     * @param \DateTime $startDate
     * @param \DateTime $endDate
     * @throws InvalidSelectorException
     * @throws \Exception
     * @throws \Facebook\WebDriver\Exception\NoSuchElementException
     * @throws null
     */
    protected function getAllTagReportsForMultiSites(\DateTime $startDate, \DateTime $endDate)
    {
        $selectElement = new WebDriverSelect($this->driver->findElement(WebDriverBy::name('publisher')));
        $sites = array_map(function(WebDriverElement $option) {
            return $option->getAttribute('value');
        }, $selectElement->getOptions());

        $this->info(sprintf('Count publisher %d', count($sites)));

        // Wait for default site report loaded
        $this->waitForJquery();
        $this->info('Site has Jquery present');
        sleep(2);
        $this->info('Waiting for overlay');
        $this->waitForOverlay('#fancybox-loading');

        foreach ($sites as $site) {

            $selectElement = new WebDriverSelect($this->driver->findElement(WebDriverBy::name('publisher')));
            sleep(2);

            $this->info(sprintf('Selecting site %s', $site));
            $selectElement->selectByValue($site);

            $this->waitForJquery();
            sleep(2);
            $this->waitForOverlay('#fancybox-loading');

            $this->logger->info(sprintf('Finished waiting for displaying report of site %s', $site));

            try {
                $this->driver->wait()->until(
                    WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::cssSelector('table[class="table"]'))
                );

                $tableElement = $this->driver->findElement(WebDriverBy::cssSelector('table[class="table"]'));
                $this->saveToCSVFileFromTable($tableElement, $startDate, $endDate, $site);
            } catch(NoSuchElementException $e) {

                $this->logger->info(sprintf('No data for site %s',$site));
            }

        }
    }


    /**
     * Selected granularity options for report
     */
    public function selectGranularity()
    {
        $selectedOptions = $this->getSelectedOptions('granularity');

        foreach ($selectedOptions as $selectedOption) {
            $intervalValue = $selectedOption->getAttribute('value');

            if ( 0 == strcmp($intervalValue, self::GRANULARITY_DAY_VALUE )) {
                $selectedOption->click();
            }
        }
    }

    /**
     * Select group by options for report
     */
    public function selectGroupBy()
    {
        $selectedOptions = $this->getSelectedOptions('groupby');

        foreach ($selectedOptions as $selectedOption) {
            $intervalValue = $selectedOption->getAttribute('value');

            if ( 0 == strcmp($intervalValue, self::GROUP_BY_SIZE_VALUE)) {
                $selectedOption->click();
            }
        }
    }

    /**
     *  Select date range for report
     * @param \DateTime $startDate
     * @param \DateTime $endDate
     */
    public function selectDateRange(\DateTime $startDate, \DateTime $endDate)
    {
        $selectDateRange = new DateSelectWidget($this->driver);
        $selectDateRange->setDateRange($startDate, $endDate);
    }

    /**
     * Find all option of selected element by name
     * @param $selectedElement
     * @return \Facebook\WebDriver\WebDriverElement[]
     */
    public function getSelectedOptions($selectedElement)
    {
        $selectElement = new WebDriverSelect($this->driver->findElement(WebDriverBy::name($selectedElement)));

        return $selectElement->getOptions();
    }

    /**
     * @param RemoteWebElement $tableElement
     * @param DateTime $startDate
     * @param DateTime $endDate
     * @param $fileName
     * @throws InvalidSelectorException
     * @throws \Exception
     * @internal param $path
     */
    public function saveToCSVFileFromTable(RemoteWebElement $tableElement, \DateTime $startDate, \DateTime $endDate, $fileName)
    {

        $path = $this->getPath($startDate,$endDate, $this->getConfig(), $fileName);

        if (!$tableElement instanceof RemoteWebElement) {
            throw new InvalidSelectorException('Invalid remove web element');
        }

        if(is_dir($path)) {
            throw new \Exception ('Path must be file');
        }

        $dataRows = $this->getDataFromTable($tableElement);
        $this->arrayToCSVFile($path, $dataRows);
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
        $this->driver->wait()->until(WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::cssSelector('table[class="table"')));
        $rowElements = $tableElement->findElements(WebDriverBy::xpath('//table/tbody/tr'));
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
     * @param $path
     * @param $dataRows
     * @throws \Exception
     */
    public function arrayToCSVFile($path, $dataRows)
    {

        if(is_dir($path)) {
            throw new \Exception ('Path must be file');
        }

        if (!is_array($dataRows)) {
            throw new \Exception ('Data to save csv file expect array type');
        }

        $file = fopen($path,'w');
        foreach ($dataRows as $dataRow) {
            fputcsv( $file, $dataRow);
        }

        fclose($file);
    }

} 