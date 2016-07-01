<?php
if(isset($_POST['confirmUserUnban'])){
    if($userUUID){
        $unbanUserObj = new UserManager($db, $systemLog, $emailQueue);

        if($unbanUserObj->loadUser($userUUID)){
            $unbanUserObj->setUserDisabled(false);

            if ($unbanUserObj->saveUser()) {
                $systemLog->setAction("USER_UNBAN");
                $systemLog->setDetail("User UUID", $userUUID);
                $systemLog->setDetail("User Name", $unbanUserObj->getFullName());
                if (!empty($_POST['banReason'])) {
                    $systemLog->setDetail("Reason", $_POST['banReason']);
                } else {
                    $systemLog->setDetail("Reason", "No Reason Provided");
                }
                $systemLog->saveEntry();

                $systemMessages->addMessage($unbanUserObj->getFullName() . " has been unbanned.", "success");
                $cdcMastery->redirect("/admin/users/" . $userUUID);
            } else {
                $systemLog->setAction("ERROR_USER_UNBAN");
                $systemLog->setDetail("User UUID", $userUUID);
                $systemLog->setDetail("User Name", $unbanUserObj->getFullName());
                if (!empty($_POST['banReason'])) {
                    $systemLog->setDetail("Reason", $_POST['banReason']);
                } else {
                    $systemLog->setDetail("Reason", "No Reason Provided");
                }
                $systemLog->setDetail("Error", $unbanUserObj->error);
                $systemLog->saveEntry();

                $systemMessages->addMessage($unbanUserObj->getFullName() . " could not be unbanned.  The error has been logged.", "danger");
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
                        Unban User
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
                    <form action="/admin/users/<?php echo $userUUID; ?>/unban" method="POST">
                        <input type="hidden" name="confirmUserUnban" value="1">
                        Unbanning <?php echo $objUser->getFullName(); ?> will allow them to access the site again. Are
                        you sure you want to do this?  Enter a reason for lifting the ban and click continue to proceed, otherwise,
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