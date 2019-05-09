<?php


namespace Killbot;

class Logger
{

    /**
     * Stores the kill json in a file
     *
     * @param $killId
     * @param $data
     */
    static function storeKillJson($killId, $data)
    {

        $path = Utils::getKillPath();
        $filename = $killId . '.json';

        Utils::writeFile($data, $path, $filename, 'w');
    }

    static function log($string, $type = 'ERROR')
    {

        $path = Utils::getLogPath();

        $log = '[' . date('Y-m-d H:i:s') . '] ';
        $log .= str_pad('[' . $type . '] ', 10, ' ', STR_PAD_RIGHT);
        $log .= $string . PHP_EOL;

        echo $log;

        Utils::writeFile($log, $path, 'killbot.log', 'a+');
    }
}