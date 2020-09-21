<?php

use CDCMastery\Models\Email\EmailQueue;
use DI\Container;
use Monolog\Logger;

/** @var Container $c */
$c = require "../Bootstrap.php";

try {
    $log = $c->get(Logger::class);
    $queue = $c->get(EmailQueue::class);

    $log->debug('email queue start');
    $queue->process();
    $log->debug('email queue processed');
} catch (Throwable $e) {
    if (isset($log)) {
        $log->debug($e);
        $log->emergency('email queue execution error');
    }
}