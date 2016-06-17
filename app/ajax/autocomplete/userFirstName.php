<?php
/**
 * Created by PhpStorm.
 * User: Claude Bing
 * Date: 10/8/2015
 * Time: 12:40 PM
 */
if(isset($_SESSION['vars']['get']['term'])) {
    $stmt = $db->prepare("SELECT DISTINCT(userFirstName) FROM userData WHERE userFirstName LIKE CONCAT('%',?,'%') ORDER BY userFirstName ASC");
    $stmt->bind_param("s", $_SESSION['vars']['get']['term']);

    if ($stmt->execute()) {
        $stmt->bind_result($userFirstName);
        while ($stmt->fetch()) {
            $jsonArray[] = ucfirst($userFirstName);
        }
        
        $stmt->close();
        
        if (isset($jsonArray) && is_array($jsonArray) && !empty($jsonArray)) {
            echo json_encode($jsonArray);
        }
    }
    else{
        $sqlError = $stmt->error;
        $stmt->close();

        $log->setAction("ERROR_AJAX_FIRST_NAME_AUTOCOMPLETE_LIST");
        $log->setDetail("Search Term",$_SESSION['vars']['get']['term']);
        $log->setDetail("MySQL Error",$sqlError);
        $log->saveEntry();
    }
}
else{
    $log->setAction("AJAX_DIRECT_ACCESS");
    $log->setDetail("CALLING SCRIPT","/ajax/autocomplete/userFirstName");
    $log->saveEntry();

    $sysMsg->addMessage("Direct access to this script is not authorized.","danger");
    $cdcMastery->redirect("/errors/403");
}