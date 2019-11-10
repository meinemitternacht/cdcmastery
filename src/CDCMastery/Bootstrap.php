<?php
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 7/2/2017
 * Time: 11:12 AM
 */

use DI\Container;
use DI\ContainerBuilder;
use Monolog\Logger;

define('APP_DIR', realpath(__DIR__));
define('BASE_DIR', realpath(__DIR__ . '/../..'));
define('VENDOR_DIR', realpath(BASE_DIR . '/vendor'));
define('VIEWS_DIR', realpath(__DIR__ . '/Views'));

require VENDOR_DIR . '/autoload.php';

set_error_handler(static function ($severity, $message, $file, $line)
{
    if (!(error_reporting() & $severity)) {
        return;
    }

    throw new ErrorException($message, 0, $severity, $file, $line);
});

try{
    $builder = new ContainerBuilder;
    $container = $builder->addDefinitions(__DIR__ . '/DIConfig.php')->build();
    $container->get(\mysqli::class);
} catch (Exception $e) {
    if ($container instanceof Container) {
        try {
            $log = $container->get(Logger::class);
            $log->alert('site offline :: ' . $e);
        } catch (Throwable $e) {
            fwrite(STDERR, 'site offline :: ' . $e);
        }
    }

    http_response_code(500);
    exit("Site currently offline.  Check back soon!");
}

return $container;