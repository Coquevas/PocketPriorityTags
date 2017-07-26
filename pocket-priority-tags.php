<?php

error_reporting(E_ALL | E_STRICT);
ini_set('display_errors', 1);
ini_set('log_errors', 0);
ini_set('html_errors', 0);

require_once __DIR__ . '/vendor/autoload.php';

use Curl\Curl;
use Duellsy\Pockpack\Pockpack;
use Pocket\OAuthConfig;
use Pocket\PriorityTags;

// TODO: Migrate to Composer package (and pull request)
require_once __DIR__ . '/src/URLResolver/URLResolver.php';


$arguments = new \cli\Arguments();
// TODO: Improve the debug mode to return valuable and comprehensible info or replace with tests
$arguments->addFlag(array('debug'), 'Turn on verbose output');
$arguments->addFlag(array('dry-run', 'd'), 'Don\'t apply changes');
$arguments->addFlag(array('silent', 's'), 'Only error output');
$arguments->addFlag(array('help', 'h'), 'Show this help screen');
$arguments->parse();
if ($arguments['help']) {
    echo "\n";
    echo $arguments->getHelpScreen();
} else {
    /*
     * Bind socket on specific $port is safe operation for concurrent execution. Operation system will make sure that there
     * is no other process which bound socket to same port. You just need to check return value. If script crashes then
     * operation system will unbind the port automatically.
     *
     * http://stackoverflow.com/questions/1861321/how-to-prevent-multiples-instances-of-a-script/1861394#1861394
     */
    $port = 19284;
    $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
    if (false === $socket) {
        throw new Exception("can't create socket: " . socket_last_error($socket));
    }

    // hide warning, because error will be checked manually
    if (false === @socket_bind($socket, '127.0.0.1', $port)) {
        // some instance of the script is running
        return false;
    } else {

        $priorityTags = new PriorityTags(
            new Curl(),
            new Pockpack(OAuthConfig::getConsumerKey(), OAuthConfig::getAccessToken()),
            new URLResolver(),
            $arguments['debug'],
            $arguments['dry-run'],
            $arguments['silent']
        );

        try {
            $priorityTags->run();
        } catch (Exception $e) {
            if ($arguments['debug']) {
                var_dump($e->getMessage());
            } else {
                echo "Error connecting to the Pocket API" . PHP_EOL;
            }
        }
        return $socket;
    }
}