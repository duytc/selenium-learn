<?php


namespace Tagcade\Service\Fetcher\Fetchers\Lkqd\Widget;


use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;

class ReportTypeSelectWidget extends AbstractWidget
{
    /**
     * @param RemoteWebDriver $driver
     */
    public function __construct(RemoteWebDriver $driver)
    {
        parent::__construct($driver);
    }

    /**
     * @param $optionIndex
     * @return $this
     */
    public function setReportType($optionIndex)
    {
        // click the drop down button
        $this->driver->findElement(WebDriverBy::xpath('//div[@class="reporttype-box"]/div[1]/button'))->click();
        $xpath = sprintf('//div[@class="reporttype-box"]/div[1]/ul[1]/li[%d]/a', $optionIndex);
        $this->driver->findElement(WebDriverBy::xpath($xpath))->click();
        return $this;
    }
}