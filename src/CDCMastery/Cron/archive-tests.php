<?php
declare(strict_types=1);
declare(ticks=1);

use CDCMastery\Models\Tests\Archive\TestArchiver;
use DI\Container;
use Monolog\Logger;

/** @var Container $c */
$c = require realpath(__DIR__) . "/../Bootstrap.php";

try {
    $log = $c->get(Logger::class);
    $archiver = $c->get(TestArchiver::class);

    if ($argc > 1) {
        array_shift($argv);          // script name
        $param = array_shift($argv); // first parameter

        if ($param === '--dry-run') {
            $archiver->setDryRun(true);
        }
    }

    $log->debug('test archiver start');
    $archiver->process();
    $log->debug('test archiver finish');
} catch (Throwable $e) {
    if (isset($log)) {
        $log->debug($e);
        $log->emergency('test archiver execution error');
    }
}
