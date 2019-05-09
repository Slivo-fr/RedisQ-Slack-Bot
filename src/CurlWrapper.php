<?php

namespace Killbot;

use Exception;
use Settings;

class CurlWrapper
{
    const METHOD_GET = 'GET';
    const METHOD_POST = 'POST';

    /**
     * Performs an http request
     * @param string $url
     * @param string $method
     * @param string $data
     *
     * @return bool|string
     * @throws Exception
     */
    static public function curlRequest($url, $method = self::METHOD_GET, $data = null)
    {
        $ch = curl_init();

        if ($ch != false) {
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_USERAGENT, Settings::$HTTP_HEADER);

            if ($method == self::METHOD_POST) {
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            }

            $output = curl_exec($ch);
            self::handleErrors($output, $ch);
            curl_close($ch);

            return $output;
        }

        throw new Exception('Unable to initiate curl');
    }

    /**
     * Performs a get http request
     * @param string $url
     * @return bool|string
     * @throws Exception
     */
    static public function get($url) {
        return self::curlRequest($url);
    }

    /**
     * Performs a post http request
     * @param string $url
     * @param $data
     * @return bool|string
     * @throws Exception
     */
    static public function post($url, $data) {
        return self::curlRequest($url, self::METHOD_POST, $data);
    }

    /**
     * @param $output
     * @param $ch
     * @throws Exception
     */
    static protected function handleErrors($output, $ch) {

        if($output === false)
        {
            throw new Exception('Curl error : ' . curl_error($ch));
        }

        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($http_code >= 400) {
            throw new Exception('HTTP error : ' . $http_code . ' on ' . curl_getinfo($ch, CURLINFO_EFFECTIVE_URL));

        }
    }
}