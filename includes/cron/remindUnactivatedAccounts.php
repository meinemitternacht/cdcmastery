<?php
/**
 * Created by PhpStorm.
 * User: claude
 * Date: 6/8/16
 * Time: 6:29 PM
 */

/*
 * This script reminds users that have not logged in (or activated their account) that they have not finished the signup process.
 */
define('BASE_PATH', realpath(__DIR__) . "/../..");
require BASE_PATH . '/includes/bootstrap.inc.php';

$dateObj = new DateTime();
$dateObj->modify("-3 days");
$limitDate = $dateObj->format("Y-m-d 00:00:00");

$res = $db->query("SELECT uuid FROM `userData` 
                      WHERE userData.userLastActive IS NULL 
                        AND userData.userLastLogin IS NULL 
                        AND userData.userDateRegistered < '".$limitDate."'
                        AND userData.reminderSent IS NULL
                        ORDER BY userData.userLastName ASC");

if($res->num_rows > 0){
    echo "There are ".$res->num_rows." user(s) eligible for reminder.  Processing...".PHP_EOL;

    $remUserObj = new user($db,$log,$emailQueue);
    $testManager = new testManager($db, $log, $afsc);
    $activateObj = new userActivation($db, $log, $emailQueue);

    $remindedUserList = Array();

    while($userObjRow = $res->fetch_assoc()) {
        if ($remUserObj->loadUser($userObjRow['uuid'])) {
            $userFullName = $remUserObj->getFullName();
            $error = false;

            echo "Reminding user " . $userFullName . "...";

            $accountCreatedDateObj = new DateTime($remUserObj->getUserDateRegistered());
            $accountCreatedDateObj->modify("+30 days");
            $accountExpireDate = $accountCreatedDateObj->format("F j, Y, g:i a");

            $emailSender = "support@cdcmastery.com";
            $emailRecipient = $remUserObj->getUserEmail();
            $emailSubject = "Account Reminder for CDCMastery";

            $emailBodyHTML = <<<HTML
<html>
<head>
    <title>Account Reminder for CDCMastery</title>
</head>
<body>
$userFullName,<br>
<br>
You created an account on CDCMastery recently, but you have not logged in.  If you are having issues using the site, or 
require us to load an AFSC that is not present on the site, we welcome you to create a ticket using our helpdesk: 
<a href="http://helpdesk.cdcmastery.com/">helpdesk.cdcmastery.com</a>.  Please note:  In order to add AFSC's, we will need 
a PDF copy of the CDC volumes (available on e-WORLD [formerly the WAPS catalog] or via ADLS) and a copy of the answer key.  
We are more than happy to add questions and answers, and always strive to have the most up-to-date information.<br>
<br>
If you no longer wish to utilize your account, you may simply ignore this e-mail.  Your account will be automatically 
removed on $accountExpireDate (UTC) if you do not activate it and log in at least once.  Let us know if you have any questions.<br>
<br>
Regards,<br>
<br>
CDCMastery.com Support
</body>
</html>
HTML;


            $emailBodyText = <<<TXT
$userFullName,

You created an account on CDCMastery recently, but you have not logged in.  If you are having issues using the site, or 
require us to load an AFSC that is not present on the site, we welcome you to create a ticket using our helpdesk: 
http://helpdesk.cdcmastery.com  Please note:  In order to add AFSC's, we will need a PDF copy of the CDC volumes 
(available on e-WORLD [formerly the WAPS catalog] or via ADLS) and a copy of the answer key.  We are more than happy to 
add questions and answers, and always strive to have the most up-to-date information.

If you no longer wish to utilize your account, you may simply ignore this e-mail.  Your account will be automatically 
removed on $accountExpireDate (UTC) if you do not activate it or log in at least once.  Let us know if you have any questions.

Regards,

CDCMastery.com Support
TXT;

            $queueUser = isset($_SESSION['userUUID']) ? $_SESSION['userUUID'] : "SYSTEM";

            if(!$emailQueue->queueEmail($emailSender, $emailRecipient, $emailSubject, $emailBodyHTML, $emailBodyText, $queueUser)){
                $log->setAction("ERROR_CRON_RUN_REMIND_UNUSED_ACCOUNTS");
                $log->setDetail("User Name",$userFullName);
                $log->setDetail("User UUID",$userObjRow['uuid']);
                $log->saveEntry();

                echo "We could not remind the following user: " . $userFullName . PHP_EOL;

                exit(1);
            }
            else{
                $remUserObj->setReminderSent(true);
                $remUserObj->saveUser();

                echo "...Done" . PHP_EOL;
            }
        }
    }

    $log->setAction("CRON_RUN_REMIND_UNUSED_ACCOUNTS");
    $log->setDetail("Users Reminded",$res->num_rows);
    $log->saveEntry();

    echo "Done reminding unused user accounts." . PHP_EOL;
}
else{
    echo "There are no accounts eligible for reminder." . PHP_EOL;
}