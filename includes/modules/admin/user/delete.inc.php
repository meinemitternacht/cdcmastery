<?php
if(isset($_POST['confirmUserDelete'])){
    if(empty($_POST['deleteReason'])){
        $sysMsg->addMessage("You must enter a reason why you are deleting this user.","warning");
        $cdcMastery->redirect("/admin/users/" . $userUUID . "/delete");
    }

    if($userUUID){
        $error = false;
        $delUserObj = new user($db,$log,$emailQueue);

        if($delUserObj->loadUser($userUUID)){
            $userFullName = $delUserObj->getFullName();

            if($roles->getRoleType($delUserObj->getUserRole()) != "admin") {
                $authObj = new auth($userUUID,$log,$db,$roles,$emailQueue);
                $testManager = new testManager($db,$log,$afsc);
                $userTestList = $testManager->getTestUUIDList($userUUID);

                if(!$authObj->getActivationStatus()){
                    $activateObj = new userActivation($db,$log,$emailQueue);
                    if(!$activateObj->deleteUserActivationToken($userUUID)){
                        $sysMsg->addMessage("User activation token not cleared.","danger");
                    }
                }

                if(!$log->clearLogEntries($userUUID)){
                    $sysMsg->addMessage("Log Entries not cleared.","danger");
                    $error = true;
                }

                if(!empty($userTestList)){
                    if(!$testManager->deleteTests($userTestList)){
                        $sysMsg->addMessage("Tests not cleared.","danger");
                        $error = true;
                    }
                }

                if(!$assoc->deleteUserAFSCAssociations($userUUID)){
                    $sysMsg->addMessage("AFSC Associations not cleared.","danger");
                    $error = true;
                }

                if(!$assoc->deleteUserSupervisorAssociations($userUUID)){
                    $sysMsg->addMessage("Supervisor Associations not cleared.","danger");
                    $error = true;
                }

                if(!$assoc->deleteUserTrainingManagerAssociations($userUUID)){
                    $sysMsg->addMessage("Training Manager Associations not cleared.","danger");
                    $error = true;
                }

                if(!$delUserObj->deleteUser($userUUID)){
                    $sysMsg->addMessage("UserData table entry not cleared.","danger");
                    $error = true;
                }

                if (!$error) {
                    $log->setAction("USER_DELETE_PROCESS_COMPLETE");
                    $log->setDetail("User UUID", $userUUID);
                    $log->setDetail("User Name", $userFullName);
                    if (!empty($_POST['deleteReason'])) {
                        $log->setDetail("Reason", $_POST['deleteReason']);
                    } else {
                        $log->setDetail("Reason", "No Reason Provided");
                    }
                    $log->saveEntry();

                    $sysMsg->addMessage($delUserObj->getFullName() . " has been deleted.","success");
                    $cdcMastery->redirect("/admin/users");
                } else {
                    $log->setAction("ERROR_USER_DELETE_PROCESS");
                    $log->setDetail("User UUID", $userUUID);
                    $log->setDetail("User Name", $userFullName);
                    if (!empty($_POST['deleteReason'])) {
                        $log->setDetail("Reason", $_POST['deleteReason']);
                    } else {
                        $log->setDetail("Reason", "No Reason Provided");
                    }
                    $log->setDetail("Errors",$sysMsg->retrieveMessages());
                    $log->saveEntry();

                    $sysMsg->addMessage($delUserObj->getFullName() . " could not be deleted.  The error has been logged.","danger");
                    $cdcMastery->redirect("/admin/users" . $userUUID);
                }
            }
            else{
                $log->setAction("ERROR_USER_DELETE_NOT_AUTH");
                $log->setDetail("User UUID",$userUUID);
                if (!empty($_POST['deleteReason'])) {
                    $log->setDetail("Reason", $_POST['deleteReason']);
                } else {
                    $log->setDetail("Reason", "No Reason Provided");
                }
                $log->saveEntry();

                $sysMsg->addMessage("Administrators cannot be deleted.","danger");
                $cdcMastery->redirect("/admin/users/" . $userUUID);
            }
        }
    } else{
        $sysMsg->addMessage("No User UUID was provided.","warning");
        $cdcMastery->redirect("/admin/users/" . $userUUID);
    }
}
else{ ?>
    <div class="container">
        <div class="row">
            <div class="4u">
                <section>
                    <header>
                        <h2><em><?php echo $objUser->getFullName(); ?></em></h2>
                    </header>
                </section>
                <div class="sub-menu">
                    <div class="menu-heading">
                        Delete User
                    </div>
                    <ul>
                        <li><a href="/admin/users/<?php echo $userUUID; ?>"><i class="icon-inline icon-20 ic-arrow-left"></i>Return to user manager</a></li>
                    </ul>
                </div>
            </div>
            <div class="6u">
                <section>
                    <header>
                        <h2>Confirm User Delete</h2>
                    </header>
                    <br>
                    <form action="/admin/users/<?php echo $userUUID; ?>/delete" method="POST">
                        <input type="hidden" name="confirmUserDelete" value="1">
                        <span style="font-size:4em;font-weight:900;">WARNING!</span><br>
                        Deleting <?php echo $objUser->getFullName(); ?> will remove <strong>ALL INFORMATION</strong> about the user
                        from the database, including tests, log entries, and all associated data. Are you sure you want to do
                        this?  Enter a reason below and click continue to proceed, otherwise,
                        <a href="/admin/users/<?php echo $userUUID; ?>">return to the user manager</a>.
                        <br>
                        <br>
                        <textarea name="deleteReason" style="width:60%;height:5em"></textarea>
                        <br>
                        <br>
                        <input type="submit" value="Continue">
                    </form>
                </section>
            </div>
        </div>
    </div>
<?php
}