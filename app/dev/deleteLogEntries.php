<?php
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 9/29/2015
 * Time: 10:26 PM
 */

$stmt = $db->prepare("SELECT uuid FROM systemLog WHERE action='TEST_DATA_DEBUG'");

if($stmt->execute()){
    $stmt->bind_result($logUUID);

    while($stmt->fetch()){
        $logUUIDArray[] = $logUUID;
    }
}

$stmt->close();

$stmt = $db->prepare("DELETE FROM systemLogData WHERE logUUID = ?");
foreach($logUUIDArray as $deleteUUID){
    $stmt->bind_param("s",$deleteUUID);

    if($stmt->execute()){
        echo "Deleted logUUID ".$deleteUUID." -- Affected rows: ".$stmt->affected_rows."<br>";
    }
    else{
        echo "Could not delete logUUID ".$deleteUUID."<br>";
    }
}

$stmt->close();

$stmt = $db->prepare("DELETE FROM systemLog WHERE uuid = ?");
foreach($logUUIDArray as $deleteSysLogUUID){
    $stmt->bind_param("s",$deleteSysLogUUID);

    if($stmt->execute()){
        echo "Deleted logUUID ".$deleteSysLogUUID." -- Affected rows: ".$stmt->affected_rows."<br>";
    }
    else{
        echo "Could not delete system log row ".$deleteSysLogUUID."<br>";
    }
}