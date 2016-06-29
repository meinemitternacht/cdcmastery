<?php
/**
 * Created by PhpStorm.
 * User: claude
 * Date: 6/27/16
 * Time: 4:42 AM
 */

if(isset($_POST['userEmail'])){
    $userEmail = strtolower($_POST['userEmail']);

    if($cdcMastery->checkEmailAddress($userEmail)){
        $user->setUserEmail($userEmail);

        if($user->saveUser()){
            $log->setAction("USER_UPDATE_FLAGGED_EMAIL");
            $log->saveEntry();

            $sysMsg->addMessage("Your e-mail address has been updated successfully.  Thanks!","success");
            $cdcMastery->redirect("/");
        }
        else{
            $log->setAction("ERROR_USER_UPDATE_FLAGGED_EMAIL");
            $log->setDetail("Provided E-mail",$userEmail);
            $log->saveEntry();

            $sysMsg->addMessage("There was a problem updating your e-mail address. If you still cannot updated your e-mail address, please contact the help desk.","danger");
        }
    }
    else{
        $log->setAction("ERROR_USER_UPDATE_FLAGGED_EMAIL");
        $log->setDetail("Provided E-mail",$userEmail);
        $log->saveEntry();

        $sysMsg->addMessage("You provided an invalid e-mail address.  Please use your official government e-mail address ending in af.mil or mail.mil.","warning");
    }
}
?>
<div class="container">
    <div class="row">
        <div class="6u">
            <section>
                <header>
                    <h1>Update E-mail Address</h1>
                </header>
                You have reached this page because the system detected an error with your e-mail address.  The e-mail address
                we currently have in the system for you is: <strong><?php echo $user->getUserEmail(); ?></strong><br>
                <br>
                Please update your e-mail in order to continue.  Note:  You must use your official e-mail address on this screen.
                If you want to utilize a personal e-mail address instead, you may do so by <a href="/user/edit">editing your profile</a>.
                <br>
                <br>
                <form action="/user/update-email" method="POST">
                    <input type="text" name="userEmail" class="input_full"><br>
                    <br>
                    <input type="submit" class="button" value="Update E-mail">
                </form>
            </section>
        </div>
    </div>
</div>
