<?php
$emailString = isset($_POST['emailString']) ? $_POST['emailString'] : false;

if($emailString){
    $stmt = $db->prepare("SELECT COUNT(*) AS count FROM userData WHERE userEmail = ?");
    $stmt->bind_param("s",$emailString);
    $stmt->execute();
}
else{
    $log->setAction("AJAX_DIRECT_ACCESS");
    $log->setDetail("CALLING SCRIPT","/ajax/registration/checkEmail");
    $log->saveEntry();

    $sysMsg->addMessage("Direct access to that script is not authorized.");
    $cdcMastery->redirect("/errors/403");
}