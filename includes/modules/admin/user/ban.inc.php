<?php
if($objUser->getUserDisabled()){
    $systemMessages->addMessage("That user is already banned.", "info");
    $cdcMastery->redirect("/admin/users/" . $userUUID);
}

if(isset($_POST['confirmUserBan'])){
    if($userUUID){
        $banUserObj = new UserManager($db, $systemLog, $emailQueue);

        if($banUserObj->loadUser($userUUID)){
            if($roleManager->getRoleType($banUserObj->getUserRole()) != "admin") {
                $banUserObj->setUserDisabled(true);

                if ($banUserObj->saveUser()) {
                    $systemLog->setAction("USER_BAN");
                    $systemLog->setDetail("User UUID", $userUUID);
                    $systemLog->setDetail("User Name", $banUserObj->getFullName());
                    if (!empty($_POST['banReason'])) {
                        $systemLog->setDetail("Ban Reason", $_POST['banReason']);
                    } else {
                        $systemLog->setDetail("Ban Reason", "No Reason Provided");
                    }
                    $systemLog->saveEntry();

                    $systemMessages->addMessage($banUserObj->getFullName() . " has been banned.", "success");
                    $cdcMastery->redirect("/admin/users/" . $userUUID);
                } else {
                    $systemLog->setAction("ERROR_USER_BAN");
                    $systemLog->setDetail("User UUID", $userUUID);
                    $systemLog->setDetail("User Name", $banUserObj->getFullName());
                    if (!empty($_POST['banReason'])) {
                        $systemLog->setDetail("Ban Reason", $_POST['banReason']);
                    } else {
                        $systemLog->setDetail("Ban Reason", "No Reason Provided");
                    }
                    $systemLog->setDetail("Error", $banUserObj->error);
                    $systemLog->saveEntry();

                    $systemMessages->addMessage($banUserObj->getFullName() . " could not be banned.  The error has been logged.", "danger");
                    $cdcMastery->redirect("/admin/users/" . $userUUID);
                }
            }
            else{
                $systemLog->setAction("ERROR_USER_BAN_NOT_AUTH");
                $systemLog->setDetail("User UUID", $userUUID);
                if (!empty($_POST['banReason'])) {
                    $systemLog->setDetail("Ban Reason", $_POST['banReason']);
                } else {
                    $systemLog->setDetail("Ban Reason", "No Reason Provided");
                }
                $systemLog->saveEntry();

                $systemMessages->addMessage("Administrators cannot be banned.", "danger");
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
                        Ban User
                    </div>
                    <ul>
                        <li><a href="/admin/users/<?php echo $userUUID; ?>"><i class="icon-inline icon-20 ic-arrow-left"></i>Return to user manager</a></li>
                    </ul>
                </div>
            </div>
            <div class="6u">
                <section>
                    <header>
                        <h2>Confirm User Ban</h2>
                    </header>
                    <br>
                    <form action="/admin/users/<?php echo $userUUID; ?>/ban" method="POST">
                        <input type="hidden" name="confirmUserBan" value="1">
                        <strong>WARNING!</strong> Banning <?php echo $objUser->getFullName(); ?> will prevent them from accessing the site. Are
                        you sure you want to do this?  Enter a reason for the ban and click continue to proceed, otherwise,
                        <a href="/admin/users/<?php echo $userUUID; ?>">return to the user manager</a>.
                        <br>
                        <br>
                        <textarea name="banReason" style="width:60%;height:5em"></textarea>
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