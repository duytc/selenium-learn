<?php

namespace Tagcade\DataSource\PulsePoint\Widget;

use Facebook\WebDriver\WebDriverBy;

class RunButtonWidget extends AbstractWidget
{
    public function clickButton()
    {
        $this->driver->findElement(WebDriverBy::cssSelector('.runReportButton a'))
            ->click()
        ;
    }
}