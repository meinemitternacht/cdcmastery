<?php
$userHandleString = isset($_POST['userHandle']) ? $_POST['userHandle'] : false;

if($userHandleString){
    $stmt = $db->prepare("SELECT COUNT(*) AS count FROM userData WHERE userHandle = ?");
    $stmt->bind_param("s",$userHandleString);

    if($stmt->execute()){
        $stmt->bind_result($count);
        $stmt->fetch();

        if($count > 0){
            echo "1";
        }
        else{
            echo "0";
        }
    }
    else{
        $log->setAction("ERROR_AJAX_CHECK_USER_HANDLE");
        $log->setDetail("CALLING SCRIPT","/ajax/registration/checkHandle");
        $log->setDetail("User Handle String",$userHandleString);
        $log->setDetail("MySQL Error",$stmt->error);
        $log->saveEntry();

        echo "0";
    }
}
else{
    $log->setAction("AJAX_DIRECT_ACCESS");
    $log->setDetail("CALLING SCRIPT","/ajax/registration/checkEmail");
    $log->saveEntry();

    $sysMsg->addMessage("Direct access to that script is not authorized.");
    $cdcMastery->redirect("/errors/403");
}