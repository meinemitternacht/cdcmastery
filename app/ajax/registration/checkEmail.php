<?php
$emailString = isset($_POST['userEmail']) ? $_POST['userEmail'] : false;

if($emailString){
    /**
     * Check if it's valid first
     */
    if(!$cdcMastery->checkEmailAddress($emailString)){
        echo "That e-mail address is invalid:  It must be a properly formatted address such as first.last_optional.1@us.af.mil or sample.user_optional1.mil@mail.mil.  If you are certain your e-mail is correct, contact the help desk.";
    }
    elseif($user->getUUIDByEmail($emailString) !== false){
        echo "That e-mail address is already in use.";
    }
    else{
        echo "0";
    }
}
elseif(!isset($_POST['userEmail'])){
    $log->setAction("AJAX_DIRECT_ACCESS");
    $log->setDetail("CALLING SCRIPT","/ajax/registration/checkEmail");
    $log->saveEntry();

    $sysMsg->addMessage("Direct access to this script is not authorized.","danger");
    $cdcMastery->redirect("/errors/403");
}