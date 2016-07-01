<?php
/**
 * Created by PhpStorm.
 * User: Claude Bing
 * Date: 10/8/2015
 * Time: 12:39 PM
 */
if(isset($_SESSION['vars']['get']['term'])) {
    $stmt = $db->prepare("SELECT userHandle FROM userData WHERE userHandle LIKE CONCAT('%',?,'%') ORDER BY userHandle ASC");
    $stmt->bind_param("s", $_SESSION['vars']['get']['term']);

    if ($stmt->execute()) {
        $stmt->bind_result($userHandle);
        while ($stmt->fetch()) {
            $jsonArray[] = $userHandle;
        }
        
        $stmt->close();

        if (isset($jsonArray) && is_array($jsonArray) && !empty($jsonArray)) {
            echo json_encode($jsonArray);
        }
    }
    else {
        $sqlError = $stmt->error;
        $stmt->close();

        $systemLog->setAction("ERROR_AJAX_USER_HANDLE_AUTOCOMPLETE_LIST");
        $systemLog->setDetail("Search Term", $_SESSION['vars']['get']['term']);
        $systemLog->setDetail("MySQL Error", $sqlError);
        $systemLog->saveEntry();
    }
}
else{
    $systemLog->setAction("AJAX_DIRECT_ACCESS");
    $systemLog->setDetail("CALLING SCRIPT", "/ajax/autocomplete/userHandle");
    $systemLog->saveEntry();

    $systemMessages->addMessage("Direct access to this script is not authorized.", "danger");
    $cdcMastery->redirect("/errors/403");
}