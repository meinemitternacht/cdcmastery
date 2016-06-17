<?php
$emailString = isset($_POST['userEmail']) ? $_POST['userEmail'] : false;

if($emailString){
    $stmt = $db->prepare("SELECT COUNT(*) AS count FROM userData WHERE userEmail = ?");
    $stmt->bind_param("s",$emailString);

    if($stmt->execute()){
        $stmt->bind_result($count);
        $stmt->fetch();
        $stmt->close();

        if($count > 0){
            $emailUsed = true;
        }
        else{
            $emailUsed = false;
        }
    }
    else{
        $sqlError = $stmt->error;
        $stmt->close();
        
        $log->setAction("ERROR_AJAX_CHECK_EMAIL");
        $log->setDetail("CALLING SCRIPT","/ajax/registration/checkEmail");
        $log->setDetail("E-mail String",$emailString);
        $log->setDetail("MySQL Error",$sqlError);
        $log->saveEntry();

        $emailUsed = false;
    }
    
    /*
     * Check if it's valid first
     */
    if(!$cdcMastery->checkEmailAddress($emailString)){
        echo "That e-mail address is invalid:  It must end with '.mil'";
    }
    elseif($emailUsed){
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