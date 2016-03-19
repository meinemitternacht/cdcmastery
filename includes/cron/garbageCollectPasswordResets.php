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

$pwReset = new passwordReset($db,$log,$emailQueue);

$res = $db->query("SELECT uuid FROM `userPasswordResets` WHERE timeExpires <= (NOW())");

if($res->num_rows > 0){
    while($row = $res->fetch_assoc()){
        $uuidList[] = $row['uuid'];
    }

    $errorUUIDList = Array();
    $successUUIDList = Array();

    echo "There are ".$res->num_rows." tokens to delete. Processing...\n";

    foreach($uuidList as $passwordResetUUID){
        if(!$pwReset->deletePasswordResetToken($passwordResetUUID)){
            $errorUUIDList[] = $passwordResetUUID;
            echo "Error deleting ".$passwordResetUUID."\n";
        }
        else{
            $successUUIDList[] = $passwordResetUUID;
            echo "Deleted ".$passwordResetUUID."\n";
        }
    }

    if(isset($successUUIDList) && !empty($successUUIDList)){
        $log->setAction("CRON_RUN_GARBAGE_COLLECT_PASSWORD_RESETS");
        foreach($successUUIDList as $successUUID){
            $log->setDetail("Token UUID",$successUUID);
        }
        $log->saveEntry();
    }

    if(isset($errorUUIDList) && !empty($errorUUIDList)){
        $log->setAction("ERROR_CRON_RUN_GARBAGE_COLLECT_PASSWORD_RESETS");
        foreach($errorUUIDList as $errorUUID){
            $log->setDetail("Token UUID",$errorUUID);
        }
        $log->saveEntry();
    }

    $res->close();

    echo "Garbage collection complete.  Check the log for further information.\n";
    exit();
}
else{
    /*
     * No incomplete password reset tokens to delete.
     */
    echo "No password reset tokens are eligible for deletion.  That's great!\n";
    exit();
}