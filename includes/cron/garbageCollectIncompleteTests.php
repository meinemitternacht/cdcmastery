<?php
/**
 * Created by PhpStorm.
 * User: Claude Bing
 * Date: 2/1/2016
 * Time: 7:16 PM
 */

/*
 * This script removes old incomplete tests (over 30 days old).
 */
define('BASE_PATH', realpath(__DIR__) . "/../..");
require BASE_PATH . '/includes/bootstrap.inc.php';

$testManager = new testManager($db,$log,$afsc);

$res = $db->query("SELECT testUUID FROM `testManager` WHERE timeStarted <= (NOW() - INTERVAL 30 DAY)");

if($res->num_rows > 0){
    $eligibleTestUUIDList = $res->fetch_all();

    $errorUUIDList = Array();
    $successUUIDList = Array();

    echo "There are ".$res->num_rows." eligible tests to delete. Processing...\n";

    foreach($eligibleTestUUIDList as $eligibleTestUUID){
        if(!$testManager->deleteIncompleteTest(false,$eligibleTestUUID[0],false,true,false)){
            $errorUUIDList[] = $eligibleTestUUID[0];
            echo "Error deleting ".$eligibleTestUUID[0]."\n";
        }
        else{
            $successUUIDList[] = $eligibleTestUUID[0];
            echo "Deleted ".$eligibleTestUUID[0]."\n";
        }
    }

    if(isset($successUUIDList) && !empty($successUUIDList)){
        $log->setAction("CRON_RUN_GARBAGE_COLLECT_INCOMPLETE_TESTS");
        foreach($successUUIDList as $successUUID){
            $log->setDetail("Test UUID",$successUUID);
        }
        $log->saveEntry();
    }

    if(isset($errorUUIDList) && !empty($errorUUIDList)){
        $log->setAction("ERROR_CRON_RUN_GARBAGE_COLLECT_INCOMPLETE_TESTS");
        foreach($errorUUIDList as $errorUUID){
            $log->setDetail("Test UUID",$errorUUID);
        }
        $log->saveEntry();
    }

    $res->close();

    echo "Garbage collection complete.  Check the log for further information.\n";
    exit();
}
else{
    /*
     * No incomplete tests to delete.
     */
    echo "No tests are eligible for deletion.  That's great!\n";
    exit();
}