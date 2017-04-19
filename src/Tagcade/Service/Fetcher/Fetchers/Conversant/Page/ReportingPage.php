<?php

namespace Tagcade\Service\Fetcher\Fetchers\Conversant\Page;

use Exception;
use Facebook\WebDriver\Exception\TimeOutException;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Tagcade\Service\Fetcher\Fetchers\Conversant\Widget\DateSelectWidget;
use Tagcade\Service\Fetcher\Pages\AbstractPage;

class ReportingPage extends AbstractPage
{
    const URL = 'https://pub.valueclickmedia.com/reports/earnings';
    const LOGOUT = 'https://admin.valueclickmedia.com/corp/login';

    public function getAllTagReports(\DateTime $startDate, \DateTime $endDate)
    {
        // step 0. Report tab
        $this->selectDateRange($startDate, $endDate);

        try {
            $this->driver->wait()->until(
                WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::cssSelector("#detailed_media_grid_component_grid > div.objbox > table > tbody > tr:nth-child(2)"))
            );

            $downloadElement = $this->driver->findElement(WebDriverBy::id("detailed_media_grid_component_grid_footer_button_0"));

            $directoryStoreDownloadFile = $this->getDirectoryStoreDownloadFile($startDate, $endDate, $this->getConfig());
            $this->downloadThenWaitUntilComplete($downloadElement, $directoryStoreDownloadFile);
        } catch (TimeOutException $te) {
            $this->logger->error('No data available for selected date range.');
        } catch (Exception $exception) {
            $this->logger->error($exception->getMessage());
        }
    }

    public function logout()
    {
        $this->driver->navigate()->to(static::LOGOUT);

        return $this;
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