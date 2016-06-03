<?php
/**
 * Created by PhpStorm.
 * User: Claude Bing
 * Date: 10/1/2015
 * Time: 2:12 PM
 */

if(isset($_POST['disableConfirm'])){
    $user->setUserDisabled(true);
    $user->saveUser();

    $log->setAction("USER_ACCOUNT_DISABLE_SELF");

    if(isset($_POST['disableMessage'])){
        $log->setDetail("disableMessage",$_POST['disableMessage']);
    }

    $log->saveEntry();
    unset($_SESSION['auth']);

    if(isset($_SESSION['cdcMasteryAdmin']))
        unset($_SESSION['cdcMasteryAdmin']);

    if(isset($_SESSION['trainingManager']))
        unset($_SESSION['trainingManager']);

    if(isset($_SESSION['supervisor']))
        unset($_SESSION['supervisor']);

    if(isset($_SESSION['editor']))
        unset($_SESSION['editor']);

    if(isset($_SESSION['userUUID']))
        unset($_SESSION['userUUID']);

    if(isset($_SESSION['userName']))
        unset($_SESSION['userName']);

    if(isset($_SESSION['userEmail']))
        unset($_SESSION['userEmail']);

    if(isset($_SESSION['timeZone']))
        unset($_SESSION['timeZone']);

    $sysMsg->addMessage("Your account has been disabled.  Thank you for using CDCMastery.","success");
    $cdcMastery->redirect("/");
}
?>
<div class="container">
    <div class="row">
        <div class="6u">
            <section>
                <header>
                    <h2>Disable Account</h2>
                </header>
                <br>
                In order to disable access to your account with CDCMastery, click "I Understand" below.  Please note that you will
                no longer have the ability to log in to the site and will require a support request to reinstate your account. To
                include a message to the staff about why you deleted your account, include your message in the text area provided.

                If you do not wish to disable your account, <a href="/">click here to cancel</a>.
                <br>
                <br>
                <form action="/user/disable" method="POST">
                    <input type="hidden" name="disableConfirm" value="1">
                    <label for="disableMessage">Please let us know why you would like to disable your account</label>
                    <textarea class="input_full" name="disableMessage" style="height: 6em;"></textarea>
                    <div class="clearfix">&nbsp;</div>
                    <input type="submit" value="I Understand">
                </form>
            </section>
        </div>
    </div>
</div>