<?php

error_reporting(E_ALL | E_STRICT);
ini_set('display_errors', 1);
ini_set('log_errors', 0);
ini_set('html_errors', 0);

require_once __DIR__ . '/vendor/autoload.php';

use SocialCounters\SocialCounters;

$url = "https://news.ycombinator.com/";

printf("Simple test for SocialCounters with URL: %s".PHP_EOL, $url);
printf("Google +1: %d".PHP_EOL, SocialCounters::getPlusOnes($url, true));
printf("Facebook likes: %d".PHP_EOL, SocialCounters::getLikes($url, true));
printf("Twitter mentions (disabled since November 2015): %d".PHP_EOL, SocialCounters::getTweets($url, true));