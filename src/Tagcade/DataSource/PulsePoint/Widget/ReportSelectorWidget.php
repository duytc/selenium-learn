<?php

namespace Tagcade\DataSource\PulsePoint\Widget;

use Facebook\WebDriver\Remote\RemoteWebDriver;

class ReportSelectorWidget extends AbstractWidget
{
    private $reportTypeWidget;
    /**
     * @var DateRangeWidget
     */
    private $dateRangeWidget;
    /**
     * @var RunButtonWidget
     */
    private $runButtonWidget;

    /**
     * @param RemoteWebDriver $driver
     * @param ReportTypeWidget $reportTypeWidget
     * @param DateRangeWidget $dateRangeWidget
     * @param RunButtonWidget $runButtonWidget
     */
    public function __construct(RemoteWebDriver $driver, ReportTypeWidget $reportTypeWidget, DateRangeWidget $dateRangeWidget, RunButtonWidget $runButtonWidget)
    {
        parent::__construct($driver);
        $this->reportTypeWidget = $reportTypeWidget;
        $this->dateRangeWidget = $dateRangeWidget;
        $this->runButtonWidget = $runButtonWidget;
    }

    /**
     * @return ReportTypeWidget
     */
    public function getReportTypeWidget()
    {
        return $this->reportTypeWidget;
    }

    /**
     * @return DateRangeWidget
     */
    public function getDateRangeWidget()
    {
        return $this->dateRangeWidget;
    }

    /**
     * @return RunButtonWidget
     */
    public function getRunButtonWidget()
    {
        return $this->runButtonWidget;
    }
}