<?php
$userHandleString = isset($_POST['userHandle']) ? $_POST['userHandle'] : false;

if($userHandleString){
    $stmt = $db->prepare("SELECT COUNT(*) AS count FROM userData WHERE userHandle = ?");
    $stmt->bind_param("s", $userHandleString);

    if ($stmt->execute()) {
        $stmt->bind_result($count);
        $stmt->fetch();

        if ($count > 0) {
            echo "1";
        }
        else {
            echo "0";
        }

        $stmt->close();
    }
    else {
        $sqlError = $stmt->error;
        $stmt->close();

        $systemLog->setAction("ERROR_AJAX_CHECK_USER_HANDLE");
        $systemLog->setDetail("CALLING SCRIPT", "/ajax/registration/checkHandle");
        $systemLog->setDetail("User Handle String", $userHandleString);
        $systemLog->setDetail("MySQL Error", $sqlError);
        $systemLog->saveEntry();

        echo "0";
    }
}
elseif(!isset($_POST['userHandle'])){
    $systemLog->setAction("AJAX_DIRECT_ACCESS");
    $systemLog->setDetail("CALLING SCRIPT", "/ajax/registration/checkEmail");
    $systemLog->saveEntry();

    $systemMessages->addMessage("Direct access to this script is not authorized.", "danger");
    $cdcMastery->redirect("/errors/403");
}