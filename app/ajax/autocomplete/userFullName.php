<?php
/**
 * Created by PhpStorm.
 * User: Claude Bing
 * Date: 1/31/2016
 * Time: 02:58 PM
 */
if(isset($_SESSION['vars']['get']['term'])) {
    $stmt = $db->prepare("SELECT DISTINCT(userData.uuid), userFirstName, userLastName, userRank, baseList.baseName
                            FROM userData
                            LEFT JOIN baseList
                              ON baseList.uuid = userData.userBase
                            WHERE
                              userLastName LIKE CONCAT('%',?,'%') OR
                              userFirstName LIKE CONCAT('%',?,'%')
                            ORDER BY userLastName ASC");
    $stmt->bind_param("ss", $_SESSION['vars']['get']['term'], $_SESSION['vars']['get']['term']);

    if ($stmt->execute()) {
        $stmt->bind_result($userUUID,$userFirstName,$userLastName,$userRank,$baseName);
        while ($stmt->fetch()) {
            $jsonArray[$userUUID] = $userLastName . ", " . $userFirstName . " " . $userRank . " (" . $baseName . ")";
        }

        if (isset($jsonArray) && is_array($jsonArray) && !empty($jsonArray)) {
            echo json_encode($jsonArray);
        }
    }
}
else{
    $log->setAction("AJAX_DIRECT_ACCESS");
    $log->setDetail("CALLING SCRIPT","/ajax/autocomplete/userFullName");
    $log->saveEntry();

    $sysMsg->addMessage("Direct access to that script is not authorized.");
    $cdcMastery->redirect("/errors/403");
}