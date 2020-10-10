<?php
/** @noinspection UnusedFunctionResultInspection */
declare(strict_types=1);

/** @var ContainerInterface $c */

use CDCMastery\Models\Config\Config;
use MatthiasMullie\Minify\CSS;
use MatthiasMullie\Minify\JS;
use Monolog\Logger;
use Psr\Container\ContainerInterface;

$dir_prefix = realpath(__DIR__);
$top_dir = realpath("$dir_prefix/../");
$c = require $dir_prefix . "/../src/CDCMastery/Bootstrap.php";

try {
    $log = $c->get(Logger::class);
    $cfg = $c->get(Config::class);

    $public_dir = "{$top_dir}/public";

    $css_min = new CSS();
    $js_min = new JS();

    $css_files = $cfg->get(['twig', 'assets', 'css']);
    $js_files = $cfg->get(['twig', 'assets', 'js']);

    if ($css_files) {
        foreach ($css_files as $css_file) {
            $css_min->addFile("{$public_dir}/{$css_file}");
        }

        $tgt_file = "{$public_dir}/assets/css/bundle.min.css";
        $tgt_tmp_file = "{$tgt_file}.tmp";
        $css_min->minify($tgt_tmp_file);
        rename($tgt_tmp_file, $tgt_file);
    }

    if ($js_files) {
        foreach ($js_files as $js_file) {
            $js_min->addFile("{$public_dir}/{$js_file}");
        }

        $tgt_file = "{$public_dir}/assets/js/bundle.min.js";
        $tgt_tmp_file = "{$tgt_file}.tmp";
        $js_min->minify($tgt_tmp_file);
        rename($tgt_tmp_file, $tgt_file);
    }
} catch (Throwable $e) {
    if (isset($log)) {
        $log->alert("minify error: {$e}");
        exit(1);
    }
}