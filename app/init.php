<?php

use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;

require __DIR__ . "/autoload.php";
require __DIR__ . "/connection.php";
require __DIR__ . "/utils.php";

require __DIR__ . "/vendor/autoload.php";

if (!isset($logFile)) {
    $logFile = 'index';
}

$log = new Logger($logFile);
$log->pushHandler(new StreamHandler('php://stdout', Level::Debug));