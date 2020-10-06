<?php
declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 7/2/2017
 * Time: 11:12 AM
 */

use CDCMastery\Controllers\Errors;
use CDCMastery\Models\Config\Config;
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

    /** @noinspection PhpUnhandledExceptionInspection */
    throw new ErrorException($message, 0, $severity, $file, $line);
});

try {
    $builder = new ContainerBuilder;
    $c = $builder->addDefinitions(__DIR__ . '/DIConfig.php')->build();

    /* check database connectivity */
    $c->get(mysqli::class);

    /* check cache health */
    $cache = $c->get(Memcached::class);
    if ($cache->set('site-online', true) === false) {
        throw new RuntimeException('memcached offline');
    }

    $config = $c->get(Config::class);

    define('CDC_DEBUG', (bool)$config->get(['system', 'debug']));
    define('SYSTEM_UUID', $config->get(['system', 'uuid']));
    define('SUPPORT_EMAIL', $config->get(['email', 'username']));
    define('SUPPORT_FACEBOOK_URL', $config->get(['support', 'facebook', 'url']));
    define('SUPPORT_FACEBOOK_DISPLAY', $config->get(['support', 'facebook', 'display']));
    define('XML_ARCHIVE_DIR', $config->get(['archive', 'xml', 'directory']));
    define('XML_ARCHIVE_CUTOFF', $config->get(['archive', 'cutoff']));

    return $c;
} catch (Exception $e) {
    if (isset($c)) {
        try {
            $log = $c->get(Logger::class);
            $log->alert('site offline :: ' . $e);
            (new Errors($c->get(Logger::class),
                        $c->get(Environment::class),
                        $c->get(Session::class)))->show_500()->send();
        } catch (Throwable $e) {
            if (isset($log)) {
                $log->debug("site offline :: {$e}");
                exit;
            }

            fwrite(STDERR, 'site offline :: ' . $e);
        }
        exit;
    }

    http_response_code(500);
    echo "There was a problem processing your request.";
    exit;
}