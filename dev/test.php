<?php

// https://developer.mozilla.org/en/docs/Web/Guide/CSS/Getting_started/Selectors

use Facebook\WebDriver\WebDriverBy;

require '../vendor/autoload.php';

$webDriverFactory = new \Tagcade\WebDriverFactory();
$webDriver = $webDriverFactory->getExistingSession('f468b741-748b-4900-8fa9-be33a8442795');

$webDriver->findElement(WebDriverBy::cssSelector('select#tags-date+input+img'))
    ->click()
;

$webDriver->findElement(WebDriverBy::linkText('3'))
    ->click()
;

$webDriver->findElement(WebDriverBy::cssSelector('select#tags-date+input+img+input+img'))
    ->click()
;

$webDriver->findElement(WebDriverBy::linkText('4'))
    ->click()
;
