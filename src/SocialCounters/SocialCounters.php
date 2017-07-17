<?php

namespace SocialCounters;

use Curl\Curl;

/**
 * Class SocialCounters
 *
 * http://stackoverflow.com/questions/8853342/how-to-get-google-1-count-for-current-page-in-php
 *
 * @package SocialCounters
 */
class SocialCounters
{
    static function getRelevance($url, $debug = false)
    {
        return self::getLikes($url) + self::getPlusOnes($url) + self::getTweets($url);
    }

    static function getTweets($url, $debug = false)
    {
        $curl = new Curl();
        $curl->get('http://urls.api.twitter.com/1/urls/count.json?url=' . $url);

        $result = 0;
        if ($curl->error) {
            if ($debug) {
                echo $curl->error . PHP_EOL;
            }
        } else {
            $response = json_decode($curl->response, true);
            $result = intval($response['count']);
        }

        return $result;
    }

    static function getLikes($url, $debug = false)
    {
        $curl = new Curl();
        $curl->get('http://graph.facebook.com/?ids=' . $url);

        $result = 0;
        if ($curl->error) {
            if ($debug) {
                echo $curl->error . PHP_EOL;
            }
        } else {
            $response = json_decode($curl->response, true);

            $firstElement = reset($response);

            if (isset($firstElement) && isset($firstElement['share'])) {
                $result += intval($firstElement['share']['share_count']);
            }
        }

        return $result;
    }

    static function getPlusOnes($url, $debug = false)
    {
        //TODO: Rewrite with Curl/Curl
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, "https://clients6.google.com/rpc");
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt(
            $curl,
            CURLOPT_POSTFIELDS,
            '[{"method":"pos.plusones.get","id":"p","params":{"nolog":true,"id":"' . $url . '","source":"widget","userId":"@viewer","groupId":"@self"},"jsonrpc":"2.0","key":"p","apiVersion":"v1"}]'
        );
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-type: application/json'));
        $curlResults = curl_exec($curl);
        curl_close($curl);

        $json = json_decode($curlResults, true);

        return intval($json[0]['result']['metadata']['globalCounts']['count']);
    }
}