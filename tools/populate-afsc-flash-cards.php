<?php

use CDCMastery\Helpers\UUID;
use CDCMastery\Models\CdcData\Afsc;
use CDCMastery\Models\CdcData\AfscCollection;
use CDCMastery\Models\FlashCards\Category;
use CDCMastery\Models\FlashCards\CategoryCollection;
use Monolog\Logger;
use Psr\Container\ContainerInterface;

/** @var ContainerInterface $c */
$c = require realpath(__DIR__) . "/../src/CDCMastery/Bootstrap.php";

/** @var Logger $log */
$log = $c->get(Logger::class);
/** @var CategoryCollection $cats */
$cats = $c->get(CategoryCollection::class);
$afsc_cats = $cats->filterAfsc();
/** @var Afsc[] $tgt_afscs */
$tgt_afscs = $c->get(AfscCollection::class)->fetchAll(AfscCollection::SHOW_ALL);

$afsc_uuids = array_map(static function (Category $v): string {
    return $v->getBinding();
}, $afsc_cats);

$tgt_afscs = array_diff_key($tgt_afscs,
                            array_flip(array_filter($afsc_uuids)));

if (!$tgt_afscs) {
    echo "All AFSCs have a flash card category created\n";
    exit;
}

$new_cats = [];
$logs = [];
$u_uuid = SYSTEM_UUID;
foreach ($tgt_afscs as $tgt_afsc) {
    $cat = new Category();
    $cat->setUuid(UUID::generate());
    $cat->setType(Category::TYPE_AFSC);
    $cat->setCreatedBy($u_uuid);
    $cat->setName($tgt_afsc->getName());
    $cat->setBinding($tgt_afsc->getUuid());
    $cat->setEncrypted($tgt_afsc->isFouo()); // only set when initially creating category
    $cat->setComments($tgt_afsc->getVersion());

    $new_cats[] = $cat;
    $logs[] = "add afsc flash card category :: {$cat->getName()} [{$cat->getUuid()}] :: afsc {$tgt_afsc->getName()} [{$tgt_afsc->getUuid()}] :: user {$u_uuid}";
}

if (!$new_cats) {
    echo "No categories were created\n";
    exit(1);
}

$cats->saveArray($new_cats);

foreach ($logs as $entry) {
    $log->info($entry);
}