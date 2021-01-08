<?php
declare(strict_types=1);

use CDCMastery\Models\Config\Config;
use CDCMastery\Models\FlashCards\Category;
use CDCMastery\Models\FlashCards\CategoryCollection;
use CDCMastery\Models\Users\UserCollection;
use DI\Container;
use Monolog\Logger;

define('CRON_ROOT', realpath(__DIR__));
define('CHUNK_SIZE', 128); /* deletion batch size */

$mutex = sem_get(ftok(__FILE__, 'i'));
/** @var Container $c */
$c = require CRON_ROOT . "/../Bootstrap.php";

try {
    $log = $c->get(Logger::class);
} catch (Throwable $e) {
    exit(1);
}

if (!sem_acquire($mutex, true)) {
    $log->alert('flash cards: unable to begin garbage collection because the mutex is locked');
    exit(1);
}

try {
    $config = $c->get(Config::class);
    $users = $c->get(UserCollection::class);
    $categories = $c->get(CategoryCollection::class);

    $tgt_cats = $categories->fetchExpired();

    if (!$tgt_cats) {
        exit;
    }

    $n = count($tgt_cats);
    $log->debug("{$n} expired flash card categories to delete");

    /** @var Category[] $cat_chunk */
    foreach (array_chunk($tgt_cats, CHUNK_SIZE) as $cat_chunk) {
        $uuids = array_map(static function (Category $v): string { return $v->getUuid(); }, $cat_chunk);
        $categories->deleteArray($uuids);

        foreach ($cat_chunk as $tgt_cat) {
            $log->debug("delete flash card category :: name '{$tgt_cat->getName()}' :: user '{$tgt_cat->getCreatedBy()}'");
        }
    }

    $log->debug("finished deleting expired flash card categories");
} catch (Throwable $e) {
    if (isset($log)) {
        $log->debug($e);
        $log->emergency('cannot clean up flash card categories');
    }
} finally {
    sem_release($mutex);
}
