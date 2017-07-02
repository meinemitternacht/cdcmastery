<?php
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 7/2/2017
 * Time: 11:12 AM
 */

use DI\ContainerBuilder;

session_start();

define('APP_DIR', realpath(__DIR__));
define('BASE_DIR', realpath(__DIR__ . '/../..'));
define('VENDOR_DIR', realpath(BASE_DIR . '/vendor'));
define('VIEWS_DIR', realpath(__DIR__ . '/Views'));

require VENDOR_DIR . '/autoload.php';

/**
 * @param $severity
 * @param $message
 * @param $file
 * @param $line
 * @throws ErrorException
 */
function exceptionErrorHandler($severity, $message, $file, $line)
{
    if (!(error_reporting() & $severity)) {
        return;
    }

    throw new ErrorException($message, 0, $severity, $file, $line);
}

set_error_handler("exceptionErrorHandler");

$containerBuilder = new ContainerBuilder;
$containerBuilder->addDefinitions(__DIR__ . '/DIConfig.php');
$container = $containerBuilder->build();

try{
    $container->get(\mysqli::class);
} catch (Exception $e) {
    $log = $container->get(\Monolog\Logger::class);

    $log->alert('site offline :: ' . $e);

    http_response_code(500);
    exit("Site currently offline.  Check back soon!");
}

return $container;