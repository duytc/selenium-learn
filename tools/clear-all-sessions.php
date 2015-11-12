<?php

require dirname(dirname(__FILE__)) . '/config.php';

require ROOT_PATH . '/vendor/autoload.php';

use Facebook\WebDriver\Remote\RemoteWebDriver;

$sessions = RemoteWebDriver::getAllSessions();

foreach($sessions as $session) {
    $driver = RemoteWebDriver::createBySessionID($session['id']);
    $driver->quit();

    echo sprintf("Cleared Session: %s\n", $session['id']);
}

unlink(SESSION_FILE);
echo sprintf("Deleted session file\n");