<?php


namespace Killbot;


use Exception;
use Settings;

class Utils
{

    /**
     * @param string $data
     * @param string $path
     * @param string $filename
     * @param string $mode
     * @throws Exception
     */
    public static function writeFile($data, string $path, string $filename, string $mode)
    {
        self::createPath($path);

        $file = fopen($path . $filename, $mode);

        if ($file != false) {
            fwrite($file, $data);
            fclose($file);
        } else {
            throw new Exception('Unable to write file ' .$filename);
        }
    }

    public static function createPath($path)
    {
        if (!file_exists($path)) {
            mkdir($path, 0755, true);
        }
    }

    public static function getUnprocessedPath() {
        return __DIR__ . '/../' . Settings::$UNPROCESSED_PATH;
    }

    public static function getLogPath(): string
    {
        return __DIR__ . '/../' . Settings::$LOG_PATH;
    }

    public static function getKillPath() {
        return self::getLogPath() . 'kills' . DIRECTORY_SEPARATOR;
    }
}