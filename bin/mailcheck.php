<?php

require_once('vendor/autoload.php');

use Killbot\AlertHandler;

AlertHandler::sendAlertMail('This is a test error message', __FILE__, __LINE__);

