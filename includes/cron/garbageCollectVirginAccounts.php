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
    
    $delUserObj = new CDCMastery\UserManager($db, $systemLog, $emailQueue);
    $testManager = new CDCMastery\TestManager($db, $systemLog, $afscManager);
    $activateObj = new CDCMastery\UserActivationManager($db, $systemLog, $emailQueue);

    $deletedUserList = Array();

    while($userObjRow = $res->fetch_assoc()) {
        if ($delUserObj->loadUser($userObjRow['uuid'])) {
            $userFullName = $delUserObj->getFullName();
            $deletedUserList[$userObjRow['uuid']] = $userFullName;
            $error = false;

            echo "Deleting user " . $userFullName . "...";

            $authObj = new CDCMastery\AuthenticationManager($userObjRow['uuid'], $systemLog, $db, $roleManager, $emailQueue);
            $userTestList = $testManager->getTestUUIDList($userObjRow['uuid']);

            if (!$authObj->getActivationStatus()) {
                if (!$activateObj->deleteUserActivationToken($userObjRow['uuid'])) {
                    $errors[] = "User activation token not cleared.";
                    $error = true;
                }
            }

            if (!$systemLog->clearLogEntries($userObjRow['uuid'], true)) {
                $errors[] = "Log Entries not cleared.";
                $error = true;
            }

            if (!empty($userTestList)) {
                if (!$testManager->deleteTests($userTestList)) {
                    $errors[] = "Tests not cleared.";
                    $error = true;
                }
            }

            if (!$associationManager->deleteUserAFSCAssociations($userObjRow['uuid'], true)) {
                $errors[] = "AFSC Associations not cleared.";
                $error = true;
            }

            if (!$associationManager->deleteUserSupervisorAssociations($userObjRow['uuid'], true)) {
                $errors[] = "Supervisor Associations not cleared.";
                $error = true;
            }

            if (!$associationManager->deleteUserTrainingManagerAssociations($userObjRow['uuid'], true)) {
                $errors[] = "Training Manager Associations not cleared.";
                $error = true;
            }

            if (!$delUserObj->deleteUser($userObjRow['uuid'], true)) {
                $errors[] = "UserData table entry not cleared.";
                $error = true;
            }

            if($error){
                $systemLog->setAction("ERROR_CRON_RUN_GARBAGE_COLLECT_VIRGIN_ACCOUNTS");
                $systemLog->setDetail("User Name", $userFullName);
                $systemLog->setDetail("User UUID", $userObjRow['uuid']);

                foreach($errors as $errorMsg){
                    echo $errorMsg . PHP_EOL;
                    $systemLog->setDetail("Error", $errorMsg);
                }

                $systemLog->saveEntry();

                echo "We could not fully delete the following user: " . $userFullName . PHP_EOL;

                exit(1);
            }
            else{
                echo "...Done" . PHP_EOL;
            }
        }
    }

    $systemLog->setAction("CRON_RUN_GARBAGE_COLLECT_VIRGIN_ACCOUNTS");
    $systemLog->setDetail("Users Deleted", $res->num_rows);

    if(!empty($deletedUserList)){
        foreach($deletedUserList as $deletedUserUUID => $deletedUserName){
            $systemLog->setDetail("Deleted User", "Name: ".$deletedUserName." UUID: ".$deletedUserUUID);
        }
    }

    $systemLog->saveEntry();

    echo "Done deleting virgin user accounts." . PHP_EOL;
}
else{
    echo "There are no accounts eligible for deletion." . PHP_EOL;
}