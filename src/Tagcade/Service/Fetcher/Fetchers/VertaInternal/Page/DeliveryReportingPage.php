<?php

namespace Tagcade\Service\Fetcher\Fetchers\VertaInternal\Page;

use Facebook\WebDriver\WebDriverBy;
use Tagcade\Service\Fetcher\Fetchers\VertaInternal\Util\VertaInternalUtil;
use Tagcade\Service\Fetcher\Fetchers\VertaInternal\Widget\DateSelectWidget;
use Tagcade\Service\Fetcher\Pages\AbstractPage;
use Tagcade\Service\Fetcher\Params\PartnerParamInterface;
use Tagcade\Service\Fetcher\Params\VertaInternal\VertaInternalPartnerParamInterface;

class DeliveryReportingPage extends AbstractPage
{
    const URL = 'https://ssp.vertamedia.com/pages/reports';

    private $specialId;

    public function getAllTagReports(PartnerParamInterface $params)
    {
        if (!$params instanceof VertaInternalPartnerParamInterface) {
            return;
        }
        $this->specialId = 1314;
        $this->clickReportOrBarGraphSymbol();
        $this->selectReportType($params->getReportType());
        $this->selectDateRanges($params->getStartDate(), $params->getEndDate());
        $this->selectSlice($params->getSlice());
        $this->runReportAndDownload();
    }

    /**
     *
     */
    private function clickReportOrBarGraphSymbol()
    {
        try {
            /** Click Reports button */
            $reportsBtn = $this->filterElementByTagNameAndText('span', 'Reports');
            if ($reportsBtn) {
                $reportsBtn->click();
                return;
            }
            /** Click Bar Graph Symbol*/
        } catch (\Exception $e) {
            $this->clickReportOrBarGraphSymbol();
        }
    }

    /**
     * @param $reportType
     */
    private function selectReportType($reportType)
    {
        try {
            $reportTypeBtn = $this->filterElementByTagNameAndText('div', 'All');
            if ($reportTypeBtn) {
                if (preg_match('/([a-zA-Z]*)-([0-9]+)/', $reportTypeBtn->getAttribute('id'), $matches)) {
                    $this->specialId = $matches[2];
                }
            }
            $reportTypeId = '';
            switch ($reportType) {
                case 'All':
                    $reportTypeId = $this->specialId;
                    break;
                case 'Desktop':
                    $reportTypeId = $this->specialId + 1;
                    break;
                case 'Mobile App':
                    $reportTypeId = $this->specialId + 2;
                    break;
                case 'Mobile Web':
                    $reportTypeId = $this->specialId + 3;
                    break;
                case 'CTV':
                    $reportTypeId = $this->specialId + 4;
                    break;
                default:
            }

            $reportTypeId = VertaInternalUtil::getId(VertaInternalUtil::RADIO, $reportTypeId);
            $reportTypeBtn = $this->driver->findElement(WebDriverBy::id($reportTypeId));

            if (!$reportTypeBtn->isSelected()) {
                $reportTypeBtn->click();
            }
            $this->logger->info('Select report type: ' . $reportType);
        } catch (\Exception $e) {
            $this->selectReportType($reportType);
        }
    }


    /**
     * @param \DateTime $startDate
     * @param \DateTime $endDate
     * @return $this
     */
    protected function selectDateRanges(\DateTime $startDate, \DateTime $endDate)
    {
        try {
            $this->sleep(3);

            $dateWidget = new DateSelectWidget($this->driver, $this->specialId);
            $dateWidget->setDateRange($startDate, $endDate);
            $this->logger->info('Select startDate ' . $startDate->format('Y-m-d') . ', endDate ' . $endDate->format('Y-m-d'));
            return $this;
        } catch (\Exception $e) {
            $this->selectDateRanges($startDate, $endDate);
        }
        return $this;
    }


    /**
     * @param $slice
     */
    private function selectSlice($slice)
    {
        try {
            $sliceId = VertaInternalUtil::getId(VertaInternalUtil::BUTTON, $this->specialId + 28);
            $sliceComboBox = $this->driver->findElement(WebDriverBy::id($sliceId));
            $sliceComboBox->click();

            $id = sprintf('menu-%s-innerCt', $this->specialId + 29);
            $breadcrumbBox = $this->driver->findElement(WebDriverBy::id($id));

            $sliceElements = $breadcrumbBox->findElements(WebDriverBy::tagName('span'));
            foreach ($sliceElements as $sliceElement) {
                if (strtolower($sliceElement->getText()) == strtolower($slice)) {
                    $sliceElement->click();
                    break;
                }
            }

            if (strtolower($slice) == strtolower('date')) {
                return;
            }

            $reportLabel = $this->filterElementByTagNameAndText('label', 'Report');
            if (!$reportLabel) {
                return;
            }

            if (!preg_match('/(label-)([0-9]+)/', $reportLabel->getAttribute('id'), $matches)) {
                return;
            }

            $id = sprintf('panel-%s-innerCt', $matches[2] + 1);
            $breadcrumbBox = $this->driver->findElement(WebDriverBy::id($id));

            $breadcrumbs = $breadcrumbBox->findElements(WebDriverBy::tagName('div'));
            foreach ($breadcrumbs as $breadcrumb) {
                if (strtolower($breadcrumb->getText()) == strtolower($slice)) {
                    $breadcrumb->click();
                    $this->logger->info('Select slice: ' . $slice);
                    return;
                }
            }

        } catch (\Exception $e) {
            $this->selectSlice($slice);
        }
    }

    /**
     *
     */
    private function runReportAndDownload()
    {
        try {
            $downloadButtonId = VertaInternalUtil::getId(VertaInternalUtil::BUTTON, $this->specialId - 7);
            $downloadButton = $this->driver->findElement(WebDriverBy::id($downloadButtonId));
            $downloadButton->click();
            $this->logger->info('Click download report');
        } catch (\Exception $e) {
            $this->runReportAndDownload();
        }
    }
}