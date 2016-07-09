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
require '../bootstrap.inc.php';

$userList = $userManager->listUsers();
$userUUIDList = array_keys($userList);

$folderList = scandir($configurationManager->getXMLArchiveConfiguration('directory'));

unset($folderList[0]); // .
unset($folderList[1]); // ..

foreach($folderList as $folderName){
    if (!in_array($folderName, $userUUIDList)) {
        echo "Deleting " . $folderName;
        exec("rm -rf " . $configurationManager->getXMLArchiveConfiguration('directory') . $folderName);
        echo "...done!\n";
        $deletedUUIDList[] = $folderName;
    }
}

$systemLog->setAction("CRON_RUN_GARBAGE_COLLECT_XML_ARCHIVES");
if(isset($deletedUUIDList) && is_array($deletedUUIDList) && !empty($deletedUUIDList)){
    foreach($deletedUUIDList as $deletedUUID){
        $systemLog->setDetail("Folder Name", $deletedUUID);
    }
}
$systemLog->saveEntry();