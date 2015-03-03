<?php

use JoeBengalen\Logger;

error_reporting(-1);
ini_set('display_errors', 1);

// Set default timezone
date_default_timezone_set('Europe/Amsterdam');

require_once 'vendor/autoload.php';

$logFile = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'default.log';

$logger = new Logger\Logger([
    //new Logger\Handler\ErrorLogHandler(),
    new Logger\Handler\FileHandler($logFile),
    new Logger\Handler\DisplayHandler()
]);

$logger->info("User '{username}' created.", array('username' => 'JoeBengalen', 'extra' => true));
$logger->critical("Unexpected Exception occurred.", ['exception' => new \Exception('Something went horribly wrong :(')]);


