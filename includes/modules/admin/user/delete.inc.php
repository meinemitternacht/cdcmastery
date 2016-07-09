<?php
if(isset($_POST['confirmUserDelete'])){
    if(empty($_POST['deleteReason'])){
        $systemMessages->addMessage("You must enter a reason why you are deleting this user.", "warning");
        $cdcMastery->redirect("/admin/users/" . $userUUID . "/delete");
    }

    if($userUUID){
        $error = false;
        $delUserObj = new CDCMastery\UserManager($db, $systemLog, $emailQueue);

        if($delUserObj->loadUser($userUUID)){
            $userFullName = $delUserObj->getFullName();

            if($roleManager->getRoleType($delUserObj->getUserRole()) != "admin") {
                $authObj = new CDCMastery\AuthenticationManager($userUUID, $systemLog, $db, $roleManager, $emailQueue);
                $testManager = new CDCMastery\TestManager($db, $systemLog, $afscManager);
                $userTestList = $testManager->getTestUUIDList($userUUID);

                if(!$authObj->getActivationStatus()){
                    $activateObj = new CDCMastery\UserActivationManager($db, $systemLog, $emailQueue);
                    if(!$activateObj->deleteUserActivationToken($userUUID)){
                        $systemMessages->addMessage("User activation token not cleared.", "danger");
                    }
                }

                if(!$systemLog->clearLogEntries($userUUID)){
                    $systemMessages->addMessage("Log Entries not cleared.", "danger");
                    $error = true;
                }

                if(!empty($userTestList)){
                    if(!$testManager->deleteTests($userTestList)){
                        $systemMessages->addMessage("Tests not cleared.", "danger");
                        $error = true;
                    }
                }

                if(!$associationManager->deleteUserAFSCAssociations($userUUID)){
                    $systemMessages->addMessage("AFSC Associations not cleared.", "danger");
                    $error = true;
                }

                if(!$associationManager->deleteUserSupervisorAssociations($userUUID)){
                    $systemMessages->addMessage("Supervisor Associations not cleared.", "danger");
                    $error = true;
                }

                if(!$associationManager->deleteUserTrainingManagerAssociations($userUUID)){
                    $systemMessages->addMessage("Training Manager Associations not cleared.", "danger");
                    $error = true;
                }

                if(!$delUserObj->deleteUser($userUUID)){
                    $systemMessages->addMessage("UserData table entry not cleared.", "danger");
                    $error = true;
                }

                if (!$error) {
                    $systemLog->setAction("USER_DELETE_PROCESS_COMPLETE");
                    $systemLog->setDetail("User UUID", $userUUID);
                    $systemLog->setDetail("User Name", $userFullName);
                    if (!empty($_POST['deleteReason'])) {
                        $systemLog->setDetail("Reason", $_POST['deleteReason']);
                    } else {
                        $systemLog->setDetail("Reason", "No Reason Provided");
                    }
                    $systemLog->saveEntry();

                    $systemMessages->addMessage($delUserObj->getFullName() . " has been deleted.", "success");
                    $cdcMastery->redirect("/admin/users");
                } else {
                    $systemLog->setAction("ERROR_USER_DELETE_PROCESS");
                    $systemLog->setDetail("User UUID", $userUUID);
                    $systemLog->setDetail("User Name", $userFullName);
                    if (!empty($_POST['deleteReason'])) {
                        $systemLog->setDetail("Reason", $_POST['deleteReason']);
                    } else {
                        $systemLog->setDetail("Reason", "No Reason Provided");
                    }
                    $systemLog->setDetail("Errors", $systemMessages->retrieveMessages());
                    $systemLog->saveEntry();

                    $systemMessages->addMessage($delUserObj->getFullName() . " could not be deleted.  The error has been logged.", "danger");
                    $cdcMastery->redirect("/admin/users" . $userUUID);
                }
            }
            else{
                $systemLog->setAction("ERROR_USER_DELETE_NOT_AUTH");
                $systemLog->setDetail("User UUID", $userUUID);
                if (!empty($_POST['deleteReason'])) {
                    $systemLog->setDetail("Reason", $_POST['deleteReason']);
                } else {
                    $systemLog->setDetail("Reason", "No Reason Provided");
                }
                $systemLog->saveEntry();

                $systemMessages->addMessage("Administrators cannot be deleted.", "danger");
                $cdcMastery->redirect("/admin/users/" . $userUUID);
            }
        }
    } else{
        $systemMessages->addMessage("No User UUID was provided.", "warning");
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