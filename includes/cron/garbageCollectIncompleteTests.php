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

$testManager = new CDCMastery\TestManager($db, $systemLog, $afscManager);

$res = $db->query("SELECT testUUID FROM `testManager` WHERE timeStarted <= (NOW() - INTERVAL 30 DAY)");

if($res->num_rows > 0){
    while($row = $res->fetch_assoc()){
        $eligibleTestUUIDList[] = $row['testUUID'];
    }

    $errorUUIDList = Array();
    $successUUIDList = Array();

    echo "There are ".$res->num_rows." eligible tests to delete. Processing...\n";

    foreach($eligibleTestUUIDList as $eligibleTestUUID){
        if(!$testManager->deleteIncompleteTest(false,$eligibleTestUUID,false,true,false)){
            $errorUUIDList[] = $eligibleTestUUID;
            echo "Error deleting ".$eligibleTestUUID."\n";
        }
        else{
            $successUUIDList[] = $eligibleTestUUID;
            echo "Deleted ".$eligibleTestUUID."\n";
        }
    }

    if(isset($successUUIDList) && !empty($successUUIDList)){
        $systemLog->setAction("CRON_RUN_GARBAGE_COLLECT_INCOMPLETE_TESTS");
        foreach($successUUIDList as $successUUID){
            $systemLog->setDetail("Test UUID", $successUUID);
        }
        $systemLog->saveEntry();
    }

    if(isset($errorUUIDList) && !empty($errorUUIDList)){
        $systemLog->setAction("ERROR_CRON_RUN_GARBAGE_COLLECT_INCOMPLETE_TESTS");
        foreach($errorUUIDList as $errorUUID){
            $systemLog->setDetail("Test UUID", $errorUUID);
        }
        $systemLog->saveEntry();
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