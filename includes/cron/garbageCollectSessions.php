<?php
/**
 * Created by PhpStorm.
 * User: Claude Bing
 * Date: 2/1/2016
 * Time: 7:16 PM
 */

/*
 * This script removes expired password reset tokens.
 */
define('BASE_PATH', realpath(__DIR__) . "/../..");
require BASE_PATH . '/includes/bootstrap.inc.php';

$session->gc();

$systemLog->setAction("CRON_RUN_GARBAGE_COLLECT_SESSIONS");
$systemLog->saveEntry();