<?php
/**
 * Created by PhpStorm.
 * User: Claude Bing
 * Date: 10/8/2015
 * Time: 11:10 AM
 */
if(isset($_SESSION['vars']['get']['term'])) {
    $stmt = $db->prepare("SELECT userEmail FROM userData WHERE userEmail LIKE CONCAT('%',?,'%') ORDER BY userEmail ASC");
    $stmt->bind_param("s", $_SESSION['vars']['get']['term']);

    if ($stmt->execute()) {
        $stmt->bind_result($userEmail);
        while ($stmt->fetch()) {
            $jsonArray[] = $userEmail;
        }

        $stmt->close();

        if (isset($jsonArray) && is_array($jsonArray) && !empty($jsonArray)) {
            echo json_encode($jsonArray);
        }
    }
    else{
        $sqlError = $stmt->error;
        $stmt->close();

        $log->setAction("ERROR_AJAX_EMAIL_AUTOCOMPLETE_LIST");
        $log->setDetail("Search Term",$_SESSION['vars']['get']['term']);
        $log->setDetail("MySQL Error",$sqlError);
        $log->saveEntry();
    }
}
else{
    $log->setAction("AJAX_DIRECT_ACCESS");
    $log->setDetail("CALLING SCRIPT","/ajax/autocomplete/userEmail");
    $log->saveEntry();

    $sysMsg->addMessage("Direct access to this script is not authorized.","danger");
    $cdcMastery->redirect("/errors/403");
}