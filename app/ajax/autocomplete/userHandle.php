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

        if (isset($jsonArray) && is_array($jsonArray) && !empty($jsonArray)) {
            echo json_encode($jsonArray);
        }
    }
}
else{
    $log->setAction("AJAX_DIRECT_ACCESS");
    $log->setDetail("CALLING SCRIPT","/ajax/autocomplete/userHandle");
    $log->saveEntry();

    $sysMsg->addMessage("Direct access to that script is not authorized.");
    $cdcMastery->redirect("/errors/403");
}