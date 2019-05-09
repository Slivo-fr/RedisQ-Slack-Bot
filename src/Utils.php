<?php


namespace Killbot;


use Settings;

class Utils
{

    /**
     * @param string $data
     * @param string $path
     * @param string $filename
     * @param string $mode
     */
    public static function writeFile($data, string $path, string $filename, string $mode)
    {
        self::createPath($path);

        $file = fopen($path . $filename, $mode);
        fwrite($file, $data);
        fclose($file);
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