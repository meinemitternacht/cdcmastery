<?php
declare(strict_types=1);

use CDCMastery\Helpers\DateTimeHelpers;
use CDCMastery\Models\Tests\Test;
use CDCMastery\Models\Tests\TestCollection;
use DI\Container;
use Monolog\Logger;

/** @var Container $c */
$c = require realpath(__DIR__) . "/../Bootstrap.php";

try {
    $log = $c->get(Logger::class);
    $tests = $c->get(TestCollection::class);

    $log->debug('incomplete tests: start garbage collection');

    $eligible = $tests->fetchExpiredIncomplete();

    if (!$eligible) {
        goto out_exit;
    }

    $uuids = array_map(static function (Test $v): string {
        return $v->getUuid();
    }, $eligible);

    $tests->deleteArray($uuids);

    foreach ($eligible as $tgt_test) {
        $updated = $tgt_test->getLastUpdated();
        $updated_str = $updated
            ? $updated->format(DateTimeHelpers::DT_FMT_DB)
            : 'N/A';
        $log->addInfo("delete expired test :: {$tgt_test->getUuid()} :: owner {$tgt_test->getUserUuid()} :: last updated {$updated_str}");
    }

    $c = count($eligible);
    $log->addInfo("incomplete tests: deleted {$c} expired tests");

    out_exit:
    $log->debug('incomplete tests: finish garbage collection');
} catch (Throwable $e) {
    if (isset($log)) {
        $log->debug($e);
        $log->emergency('error garbage collecting incomplete tests');
    }
}