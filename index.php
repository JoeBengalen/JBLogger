<?php

use JoeBengalen\Logger;
use Psr\Log\LogLevel;

error_reporting(-1);
ini_set('display_errors', 1);

// Set default timezone
date_default_timezone_set('Europe/Amsterdam');

require_once 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

$logFile = __DIR__ . DIRECTORY_SEPARATOR . 'default.log';

$logger = new Logger\Logger([
    //new Logger\Handler\ErrorLogHandler(),
    new Logger\Handler\FileHandler($logFile),
    new Logger\Handler\DisplayHandler(),
    
    // custom handler only showing debug messages
    function ($level, $message, array $context) {
        if ($level == LogLevel::DEBUG) {
            echo "Debugging: {$message}\n";
        }
    }
]);

$logger->debug('Some debug information ...');
$logger->info("User '{username}' created.", array('username' => 'JoeBengalen', 'extra' => true));
$logger->critical("Unexpected Exception occurred.", ['exception' => new \Exception('Something went horribly wrong :(')]);


