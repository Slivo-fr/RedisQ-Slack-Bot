<?php

require_once(__DIR__ . '/../vendor/autoload.php');
require_once(__DIR__ . '/../Settings.php');

use Killbot\AlertHandler;

AlertHandler::sendAlertMail('This is a test error message', __FILE__, __LINE__);

