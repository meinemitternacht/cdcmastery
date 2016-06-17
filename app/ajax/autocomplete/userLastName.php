<?php
/**
 * Created by PhpStorm.
 * User: Claude Bing
 * Date: 10/8/2015
 * Time: 12:41 PM
 */
if(isset($_SESSION['vars']['get']['term'])) {
    $stmt = $db->prepare("SELECT DISTINCT(userLastName) FROM userData WHERE userLastName LIKE CONCAT('%',?,'%') ORDER BY userLastName ASC");
    $stmt->bind_param("s", $_SESSION['vars']['get']['term']);

    if ($stmt->execute()) {
        $stmt->bind_result($userLastName);
        while ($stmt->fetch()) {
            $jsonArray[] = ucfirst($userLastName);
        }

        $stmt->close();

        if (isset($jsonArray) && is_array($jsonArray) && !empty($jsonArray)) {
            echo json_encode($jsonArray);
        }
    }
    else {
        $sqlError = $stmt->error;
        $stmt->close();

        $log->setAction("ERROR_AJAX_LAST_NAME_AUTOCOMPLETE_LIST");
        $log->setDetail("Search Term",$_SESSION['vars']['get']['term']);
        $log->setDetail("MySQL Error",$sqlError);
        $log->saveEntry();
    }
}
else{
    $log->setAction("AJAX_DIRECT_ACCESS");
    $log->setDetail("CALLING SCRIPT","/ajax/autocomplete/userLastName");
    $log->saveEntry();

    $sysMsg->addMessage("Direct access to this script is not authorized.","danger");
    $cdcMastery->redirect("/errors/403");
}