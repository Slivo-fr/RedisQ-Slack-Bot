<?php

set_error_handler(function($code, $message, $file, $line) {
    echo $message . "\n";
    throw new ErrorException($message, 0, $code, $file, $line);
});

require_once ('vendor/autoload.php');

use Killbot\AlertHandler;
use Killbot\Killbot;
use Killbot\Settings;

try {

    $killbot = new Killbot();
    $killbot->run();
}
catch (Exception $e) {

    if (Settings::$SEND_MAIL) {
        AlertHandler::sendAlertMail($e);
    }
}
