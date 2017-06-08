<?php

namespace Tagcade\Service\Fetcher\Fetchers\PulsePoint\Widget;

use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Remote\RemoteWebElement;
use Facebook\WebDriver\WebDriverBy;
use Monolog\Logger;

abstract class AbstractWidget
{
    /**
     * @var RemoteWebDriver
     */
    protected $driver;
    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @param RemoteWebDriver $driver
     * @param Logger $logger
     */
    public function __construct(RemoteWebDriver $driver, Logger $logger = null)
    {
        $this->driver = $driver;
        $this->logger = $logger;
    }

    /**
     * @param $currentDate
     * @param $expectDate
     * @return bool
     */

    public function isPrevioustNavigator($currentDate, $expectDate)
    {

        $currentMonth = date('m', strtotime($currentDate));
        $currentYear = date('Y', strtotime($currentDate));

        $expectMonth = date('m', strtotime($expectDate));
        $expectYear = date('Y', strtotime($expectDate));;

        if (((int)$currentYear > (int)$expectYear) || ($currentYear == $expectYear && $currentMonth > $expectMonth)) {
            return true;
        }

        return false;

    }

    /**
     * @param string $tagName
     * @param $text
     * @return RemoteWebElement
     */
    public function filterElementByTagNameAndText($tagName = 'li', $text)
    {
        $classElements = $this->driver->findElements(WebDriverBy::tagName($tagName));
        if (count($classElements) < 1) {
            return null;
        }

        foreach ($classElements as $element) {
            if (!$element instanceof RemoteWebElement) {
                continue;
            }
            if (!$element->isDisplayed()) {
                continue;
            }
            if (strtolower($element->getText()) == strtolower($text)) {
                return $element;
            }
        }

        return null;
    }
}