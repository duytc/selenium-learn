<?php


namespace Tagcade\Service\Fetcher\Fetchers\Lkqd\Widget;


use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;

class ReportSourceSelectWidget extends AbstractWidget
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
    public function setReportSource($optionIndex)
    {
        // click the drop down button
        $this->driver->findElement(WebDriverBy::xpath('//div[@class="row-2"]/div[1]/div[1]/button'))->click();
        $xpath = sprintf('//div[@class="row-2"]/div[1]/div[1]/ul[1]/li[%d]/a[1]', $optionIndex);
        $element = $this->driver->findElement(WebDriverBy::xpath($xpath));
        if ($element) {
            $element->click();
        }
        return $this;
    }
}