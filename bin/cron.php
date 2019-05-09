<?php

require_once(__DIR__ . '/../vendor/autoload.php');
require_once(__DIR__ . '/../Settings.php');

use Killbot\AlertHandler;
use Killbot\Killbot;
use Killbot\Logger;

set_error_handler(
    function ($code, $message, $file, $line) {
        throw new ErrorException($message, 0, $code, $file, $line);
    }
);

set_exception_handler(
    function ($exception) {
        Logger::log($exception->getMessage());
        if (Settings::$ENV === 'PROD' && Settings::$SEND_MAIL) {
            AlertHandler::sendAlertMail($exception->getMessage(), $exception->getFile(), $exception->getLine());
        } elseif (Settings::$ENV === 'DEV') {
            throw $exception;
        }
    }
);

function runBot()
{
    $killbot = new Killbot();
    $killbot->run();
}

runBot();