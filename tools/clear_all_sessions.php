<?php

define('ROOT_PATH', dirname(dirname(__FILE__)));
require ROOT_PATH . '/vendor/autoload.php';

use Facebook\WebDriver\Remote\RemoteWebDriver;
$sessions = RemoteWebDriver::getAllSessions();

foreach($sessions as $session) {
    $driver = RemoteWebDriver::createBySessionID($session['id']);
    $driver->quit();
    echo sprintf("Cleared Session: %s\n", $session['id']);
}
