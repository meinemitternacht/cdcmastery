<?php
/**
 * Created by PhpStorm.
 * User: Claude Bing
 * Date: 2016/03/19
 * Time: 7:01 PM
 */

/*
 * This script removes XML archived tests for users who have been deleted.
 */
define('BASE_PATH', realpath(__DIR__) . "/../..");
require BASE_PATH . '/includes/bootstrap.inc.php';

$userList = $user->listUsers();
$userUUIDList = array_keys($userList);

$folderList = scandir($cfg['xml']['directory']);

unset($folderList[0]); // .
unset($folderList[1]); // ..

foreach($folderList as $folderName){
    if (!in_array($folderName, $userUUIDList)) {
        echo "Deleting " . $folderName;
        exec("rm -rf " . $cfg['xml']['directory'] . $folderName);
        echo "...done!\n";
        $deletedUUIDList[] = $folderName;
    }
}

$log->setAction("CRON_RUN_GARBAGE_COLLECT_XML_ARCHIVES");
if(isset($deletedUUIDList) && is_array($deletedUUIDList) && !empty($deletedUUIDList)){
    foreach($deletedUUIDList as $deletedUUID){
        $log->setDetail("Folder Name",$deletedUUID);
    }
}
$log->saveEntry();