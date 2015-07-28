<?php
if($objUser->getUserDisabled()){
    $sysMsg->addMessage("That user is already banned.");
    $cdcMastery->redirect("/admin/users/" . $userUUID);
}

if(isset($_POST['confirmUserBan'])){
    if($userUUID){
        $banUserObj = new user($db,$log,$emailQueue);

        if($banUserObj->loadUser($userUUID)){
            if($roles->getRoleType($banUserObj->getUserRole()) != "admin") {
                $banUserObj->setUserDisabled(true);

                if ($banUserObj->saveUser()) {
                    $log->setAction("USER_BAN");
                    $log->setDetail("User UUID", $userUUID);
                    $log->setDetail("User Name", $banUserObj->getFullName());
                    if (!empty($_POST['banReason'])) {
                        $log->setDetail("Ban Reason", $_POST['banReason']);
                    } else {
                        $log->setDetail("Ban Reason", "No Reason Provided");
                    }
                    $log->saveEntry();

                    $sysMsg->addMessage($banUserObj->getFullName() . " has been banned.");
                    $cdcMastery->redirect("/admin/users/" . $userUUID);
                } else {
                    $log->setAction("ERROR_USER_BAN");
                    $log->setDetail("User UUID", $userUUID);
                    $log->setDetail("User Name", $banUserObj->getFullName());
                    if (!empty($_POST['banReason'])) {
                        $log->setDetail("Ban Reason", $_POST['banReason']);
                    } else {
                        $log->setDetail("Ban Reason", "No Reason Provided");
                    }
                    $log->setDetail("Error", $banUserObj->error);
                    $log->saveEntry();

                    $sysMsg->addMessage($banUserObj->getFullName() . " could not be banned.  The error has been logged.");
                    $cdcMastery->redirect("/admin/users/" . $userUUID);
                }
            }
            else{
                $log->setAction("ERROR_USER_BAN_NOT_AUTH");
                $log->setDetail("User UUID",$userUUID);
                if (!empty($_POST['banReason'])) {
                    $log->setDetail("Ban Reason", $_POST['banReason']);
                } else {
                    $log->setDetail("Ban Reason", "No Reason Provided");
                }
                $log->saveEntry();

                $sysMsg->addMessage("Administrators cannot be banned.");
                $cdcMastery->redirect("/admin/users/" . $userUUID);
            }
        }
    } else{
        $sysMsg->addMessage("No User UUID was provided.");
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