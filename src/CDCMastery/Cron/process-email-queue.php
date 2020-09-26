<?php

use CDCMastery\Models\Email\EmailQueue;
use DI\Container;
use Monolog\Logger;

define('CRON_ROOT', realpath(__DIR__));

/** @var Container $c */
$c = require CRON_ROOT . "/../Bootstrap.php";

try {
    $log = $c->get(Logger::class);
    $queue = $c->get(EmailQueue::class);

    $log->debug('email queue start');
    if (!$queue->process()) {
        $log->emergency('email queue execution error');
        exit(1);
    }
    $log->debug('email queue processed');
} catch (Throwable $e) {
    if (isset($log)) {
        $log->debug($e);
        $log->emergency('email queue execution error');
    }
}