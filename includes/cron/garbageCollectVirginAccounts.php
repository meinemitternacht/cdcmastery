<?php
/**
 * Created by PhpStorm.
 * User: Claude Bing
 * Date: 2/1/2016
 * Time: 7:16 PM
 */

/*
 * This script removes "virgin" accounts over 30 days old: user has registered, but not logged in (and possibly not activated their account).
 */
define('BASE_PATH', realpath(__DIR__) . "/../..");
require BASE_PATH . '/includes/bootstrap.inc.php';

$dateObj = new DateTime();
$dateObj->modify("-30 days");
$limitDate = $dateObj->format("Y-m-d 00:00:00");

$res = $db->query("SELECT uuid FROM `userData` 
                      WHERE userData.userLastActive IS NULL 
                        AND userData.userLastLogin IS NULL 
                        AND userData.userDateRegistered < '".$limitDate."'
                        ORDER BY userData.userLastName ASC");

if($res->num_rows > 0){
    echo "There are ".$res->num_rows." user(s) eligible for deletion.  Processing...".PHP_EOL;
    
    $delUserObj = new user($db,$log,$emailQueue);
    $testManager = new testManager($db, $log, $afsc);
    $activateObj = new userActivation($db, $log, $emailQueue);

    $deletedUserList = Array();

    while($userObjRow = $res->fetch_assoc()) {
        if ($delUserObj->loadUser($userObjRow['uuid'])) {
            $userFullName = $delUserObj->getFullName();
            $deletedUserList[$userObjRow['uuid']] = $userFullName;
            $error = false;

            echo "Deleting user " . $userFullName . "...";

            $authObj = new auth($userObjRow['uuid'], $log, $db, $roles, $emailQueue);
            $userTestList = $testManager->getTestUUIDList($userObjRow['uuid']);

            if (!$authObj->getActivationStatus()) {
                if (!$activateObj->deleteUserActivationToken($userObjRow['uuid'])) {
                    $errors[] = "User activation token not cleared.";
                    $error = true;
                }
            }

            if (!$log->clearLogEntries($userObjRow['uuid'], true)) {
                $errors[] = "Log Entries not cleared.";
                $error = true;
            }

            if (!empty($userTestList)) {
                if (!$testManager->deleteTests($userTestList)) {
                    $errors[] = "Tests not cleared.";
                    $error = true;
                }
            }

            if (!$assoc->deleteUserAFSCAssociations($userObjRow['uuid'], true)) {
                $errors[] = "AFSC Associations not cleared.";
                $error = true;
            }

            if (!$assoc->deleteUserSupervisorAssociations($userObjRow['uuid'], true)) {
                $errors[] = "Supervisor Associations not cleared.";
                $error = true;
            }

            if (!$assoc->deleteUserTrainingManagerAssociations($userObjRow['uuid'], true)) {
                $errors[] = "Training Manager Associations not cleared.";
                $error = true;
            }

            if (!$delUserObj->deleteUser($userObjRow['uuid'], true)) {
                $errors[] = "UserData table entry not cleared.";
                $error = true;
            }

            if($error){
                $log->setAction("ERROR_CRON_RUN_GARBAGE_COLLECT_VIRGIN_ACCOUNTS");
                $log->setDetail("User Name",$userFullName);
                $log->setDetail("User UUID",$userObjRow['uuid']);

                foreach($errors as $errorMsg){
                    echo $errorMsg . PHP_EOL;
                    $log->setDetail("Error",$errorMsg);
                }

                $log->saveEntry();

                echo "We could not fully delete the following user: " . $userFullName . PHP_EOL;

                exit(1);
            }
            else{
                echo "...Done" . PHP_EOL;
            }
        }
    }

    $log->setAction("CRON_RUN_GARBAGE_COLLECT_VIRGIN_ACCOUNTS");
    $log->setDetail("Users Deleted",$res->num_rows);

    if(!empty($deletedUserList)){
        foreach($deletedUserList as $deletedUserUUID => $deletedUserName){
            $log->setDetail("Deleted User","Name: ".$deletedUserName." UUID: ".$deletedUserUUID);
        }
    }

    $log->saveEntry();

    echo "Done deleting virgin user accounts." . PHP_EOL;
}
else{
    echo "There are no accounts eligible for deletion." . PHP_EOL;
}