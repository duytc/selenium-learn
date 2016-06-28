<?php


namespace Tagcade\DataSource\Adtech\Page;


use Facebook\WebDriver\Exception\NoSuchElementException;
use Facebook\WebDriver\Exception\TimeOutException;
use Facebook\WebDriver\Remote\RemoteWebElement;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Facebook\WebDriver\WebDriverSelect;
use Tagcade\DataSource\Adtech\Widget\DateSelectWidget;
use Tagcade\DataSource\PulsePoint\Page\AbstractPage;

class ReportingPage extends AbstractPage {

    const URL                                   = 'https://marketplace.adtechus.com/h2/set.do';
    const PLACEMENT_FILL_RATE_REPORT_INDEX      = 3;

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

        $reportingTabCssSelector = '#marketplaceNavigationbarForthTab > a';
        $this->driver->findElement(WebDriverBy::cssSelector($reportingTabCssSelector))
            ->click()
        ;

        try {
            /** @var RemoteWebElement $orderReportIcon */
            $this->driver->switchTo()->frame($this->driver->findElement(WebDriverBy::cssSelector('#mainwindow')));

            $this->driver->findElement(WebDriverBy::id('button.orderreport.1018800001'))->click();
            sleep(3);

            $this->driver->switchTo()->defaultContent();
            $this->driver->switchTo()->frame($this->driver->findElement(WebDriverBy::cssSelector('#orderReportIframe')));

            $this->logger->debug('Select EXCEL type file to download data');
            $fileTypeElement = new WebDriverSelect($this->driver->findElement(WebDriverBy::cssSelector('#id_1')));
            $fileTypeElement->selectByVisibleText('EXCEL');


            $this->logger->debug('Click to plus icon to open tree view');
            $divTableCss = '#__UNFOLD_AREA__0';
            $divTable = $this->driver->findElement(WebDriverBy::cssSelector($divTableCss));
            $tableElement = $divTable->findElement(WebDriverBy::id('treediv_POSTLOADTREE_0'));
            $rowToClicks = $tableElement->findElement(WebDriverBy::cssSelector('#idFav > td.unfoldguicell'));
            $rowToClicks->click();

            $this->logger->debug('Select type of report');
            $this->driver->wait()->until(WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::id('treediv_POSTLOADTREE_0_34359837043')));
            $tableSub = $this->driver->findElement(WebDriverBy::id('treediv_POSTLOADTREE_0_34359837043'));
            $trElements = $tableSub->findElements(WebDriverBy::cssSelector('tr'));
            $trElements[self::PLACEMENT_FILL_RATE_REPORT_INDEX]->click();

            $this->logger->debug('Set date range for report');
            $this->selectDateRange($startDate,$endDate);

            $this->logger->debug('Click to next step');
            $nextStepElement = $this->driver->findElement(WebDriverBy::cssSelector('#button_nextstep'));
            $nextStepElement->click();

            $this->logger->debug('Click to select all type');
            try {
                $checkBoxSelectAll = $this->driver->findElement(WebDriverBy::cssSelector('#treediv_websitelist > tbody > tr:nth-child(3) > td.iconCellWidth.rowSelectionIcon'));
                $checkBoxSelectAll->click();
            } catch (NoSuchElementException $noSuchElement) {
                throw new NoSuchElementException('End date is invalid. This should be set earlier than current end date');
            }

            $this->logger->debug('Click to Order button');
            $orderButtonCss = '#button_report\2e button\2e finish';
            $this->driver->findElement(WebDriverBy::cssSelector($orderButtonCss))->click();

            $this->driver->switchTo()->defaultContent();
            $this->driver->switchTo()->frame($this->driver->findElement(WebDriverBy::cssSelector('#mainwindow')));
            $this->logger->debug('Waiting to load report data');

            $timeToWaiting = 0;
            do {
                $timeToWaiting +=5;
                sleep(5);
                $this->logger->debug(sprintf('Total time to waiting = %d', $timeToWaiting));
                $waitingImageElements = $this->driver->findElements(WebDriverBy::cssSelector('img[src="/h2/img/themeone/img/status/busy.png"]'));
                $this->logger->debug(sprintf('Count Waiting element = %d', count($waitingImageElements)));
            } while (count($waitingImageElements) >0);

           $mainWindow = $this->driver->getWindowHandle();
           $this->logger->debug('Click to download button!');
           $downloadBtn = $this->driver->findElement(WebDriverBy::cssSelector('img[src="https://marketplace.adtechus.com/h2/img/themeone/img/status/ready.png"]'));
           $directoryStoreDownloadFile =  $this->getDirectoryStoreDownloadFile($startDate,$endDate,$this->getConfig());
           $this->downloadThenWaitUntilComplete($downloadBtn , $directoryStoreDownloadFile);

            $this->driver->switchTo()->window($mainWindow);
           $this->logger->debug('Logout System!');
           $this->logoutSystem();

        } catch (NoSuchElementException $e) {
            $this->logger->warning(sprintf('Can not find element: %s', $e->getMessage()));
        } catch (TimeOutException $timeOutException) {
            $this->logger->warning('Time out exception');
        } catch (\Exception $exp) {
            $this->logger->warning('Exception!');
        }
    }

    /**
     * @param \DateTime $startDate
     * @param \DateTime $endDate
     * @return $this
     */
    protected function selectDateRange(\DateTime $startDate, \DateTime $endDate)
    {
        $dateWidget = new DateSelectWidget($this->driver);
        $dateWidget->setDateRange($startDate, $endDate);

        return $this;
    }

    protected function logoutSystem()
    {
        $logoutButtonCss = '#navLogoutItem';
        $this->driver->findElement(WebDriverBy::cssSelector($logoutButtonCss))->click();
        $confirmLogoutButtonsCss = '#button_caption\2e yes';
        $this->driver->findElement(WebDriverBy::cssSelector($confirmLogoutButtonsCss))->click();
    }

} 