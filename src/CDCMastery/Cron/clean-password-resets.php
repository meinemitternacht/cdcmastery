<?php

use CDCMastery\Models\Auth\PasswordReset\PasswordResetCollection;
use DI\Container;
use Monolog\Logger;

define('CRON_ROOT', realpath(__DIR__));

/** @var Container $c */
$c = require CRON_ROOT . "/../Bootstrap.php";

try {
    $log = $c->get(Logger::class);
    $resets = $c->get(PasswordResetCollection::class);
    $pw_resets = $resets->fetchAll();

    if (!$pw_resets) {
        exit;
    }

    $now = new DateTime();

    foreach ($pw_resets as $pw_reset) {
        if ($pw_reset->getDateExpires() < $now) {
            $resets->remove($pw_reset);
            $log->debug("delete expired password reset :: code '{$pw_reset->getUuid()}' :: user '{$pw_reset->getUserUuid()}'");
        }
    }
} catch (Throwable $e) {
    if (isset($log)) {
        $log->debug($e);
        $log->emergency('cannot clean up password resets');
    }
}