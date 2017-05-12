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
     * @param int $index
     * @return RemoteWebElement
     */
    public function filterElementByTagNameAndText($tagName = 'li', $text, $index = 0)
    {
        $classElements = $this->driver->findElements(WebDriverBy::tagName($tagName));
        if (count($classElements) < 1) {
            return null;
        }

        $filterElements = array_filter($classElements, function ($element) use ($text) {
            /** @var RemoteWebElement $element */
            return $element->isDisplayed() && strtolower($element->getText()) == strtolower($text);
        });

        if (count($filterElements) < 1) {
            return null;
        }

        $filterElements = array_values($filterElements);

        if (array_key_exists($index, $filterElements)) {
            return $filterElements[$index];
        }

        return $filterElements[count($filterElements) - 1];
    }
}