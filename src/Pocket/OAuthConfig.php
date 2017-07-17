<?php

namespace Pocket;

class OAuthConfig
{
    /**
     * @var string The Pocket API Consumer Key.
     * Don't edit this until you've authenticated with pocket.
     * You can do it here http://getpocket.com/developer/apps/new
     */
    private static $consumerKey = 'YOUR_CONSUMER_KEY';

    /**
     * @var string The OAuth Access Token.
     * Don't edit this until you've authenticated with pocket.
     * You can do it with https://github.com/jshawl/pocket-oauth-php
     */
    private static $accessToken = 'YOUR_ACCESS_TOKEN';

    static function getConsumerKey()
    {
        return getenv('POCKET_API_CONSUMER_KEY') ?: self::$consumerKey;
    }

    static function getAccessToken()
    {
        return getenv('POCKET_API_ACCESS_TOKEN') ?: self::$accessToken;
    }
}