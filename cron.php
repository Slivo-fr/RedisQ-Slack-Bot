<?php

set_error_handler(
    function ($code, $message, $file, $line) {
        echo $message . PHP_EOL;
        throw new ErrorException($message, 0, $code, $file, $line);
    }
);

require_once('vendor/autoload.php');

use Killbot\AlertHandler;
use Killbot\Killbot;
use Killbot\Settings;

function runBot()
{
    $killbot = new Killbot();
    $killbot->run();
}

if (!Settings::$DEBUG) {

    try {

        runBot();
    } catch (Exception $e) {

        if (Settings::$SEND_MAIL) {
            AlertHandler::sendAlertMail($e);
        }
    }
} else {
    runBot();
}
