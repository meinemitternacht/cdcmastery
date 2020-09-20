<?php
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 7/2/2017
 * Time: 11:12 AM
 */

use CDCMastery\Controllers\Errors;
use DI\ContainerBuilder;
use Monolog\Logger;
use Symfony\Component\HttpFoundation\Session\Session;
use Twig\Environment;

define('APP_DIR', realpath(__DIR__));
define('BASE_DIR', realpath(__DIR__ . '/../..'));
define('VENDOR_DIR', realpath(BASE_DIR . '/vendor'));
define('VIEWS_DIR', realpath(__DIR__ . '/Views'));

require VENDOR_DIR . '/autoload.php';

set_error_handler(static function ($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) {
        return;
    }

    throw new ErrorException($message, 0, $severity, $file, $line);
});

try {
    $builder = new ContainerBuilder;
    $container = $builder->addDefinitions(__DIR__ . '/DIConfig.php')->build();

    /* check database connectivity */
    $container->get(mysqli::class);

    /* check cache health */
    $cache = $container->get(Memcached::class);
    if ($cache->set('site-online', true) === false) {
        throw new RuntimeException('memcached offline');
    }
    return $container;
} catch (Exception $e) {
    if (isset($container)) {
        try {
            $log = $container->get(Logger::class);
            $log->alert('site offline :: ' . $e);
        } catch (Throwable $e) {
            fwrite(STDERR, 'site offline :: ' . $e);
        }

        (new Errors($container->get(Logger::class),
                    $container->get(Environment::class),
                    $container->get(Session::class)))->show_500()->send();
        exit;
    }

    http_response_code(500);
    echo "There was a problem processing your request.";
    exit;
}