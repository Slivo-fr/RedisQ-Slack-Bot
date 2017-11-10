<?php

require_once ('vendor/autoload.php');

use Killbot\AlertHandler;

AlertHandler::sendAlertMail(new Exception('This is a test error message'));

