<?php

namespace Tagcade\DataSource\PulsePoint\Page;

use Facebook\WebDriver\Remote\RemoteWebDriver;
use Tagcade\DataSource\PulsePoint\Widget\DateRangeWidget;

class ManagerPage extends AbstractPage
{
    const URL = 'http://exchange.pulsepoint.com/Publisher/PMRMainJT.aspx';
    /**
     * @var DateRangeWidget
     */
    private $dateRangeWidget;

    public function __construct(RemoteWebDriver $driver, DateRangeWidget $dateRangeWidget)
    {
        parent::__construct($driver);
        $this->dateRangeWidget = $dateRangeWidget;
    }

    /**
     * @return DateRangeWidget
     */
    public function getDateRangeWidget()
    {
        return $this->dateRangeWidget;
    }
}