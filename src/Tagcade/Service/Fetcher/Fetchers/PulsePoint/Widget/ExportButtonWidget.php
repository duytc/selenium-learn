<?php

namespace Tagcade\Service\Fetcher\Fetchers\PulsePoint\Widget;

use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;

class ExportButtonWidget extends AbstractWidget
{
    const BUTTON_SEL = 'a.exportButton.button';

    public function getElement()
    {
        return $this->driver->findElement($this->getElementSel());
    }

    public function clickButton()
    {
        $this->driver->wait()->until(function (RemoteWebDriver $driver) {
            return $driver->executeScript("return !!window.jQuery && window.jQuery.active == 0");
        });

        $this->driver->wait(30, 1000)->until(
            WebDriverExpectedCondition::not(WebDriverExpectedCondition::presenceOfAllElementsLocatedBy(WebDriverBy::cssSelector('div.blockUI')))
        );

        $this->driver->wait()->until(WebDriverExpectedCondition::elementToBeClickable($this->getElementSel()));
        $this->getElement()->click();
    }

    protected function getElementSel()
    {
        return WebDriverBy::cssSelector(static::BUTTON_SEL);
    }
}