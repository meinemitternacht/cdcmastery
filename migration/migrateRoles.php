<?php
define('BASE_PATH', realpath(__DIR__ . '/../'));
define('APP_BASE', realpath(__DIR__ . '/../app'));

include "../includes/bootstrap.inc.php";

$oldDB = new mysqli($cfg['db']['host'], $cfg['db']['user'], $cfg['db']['pass'], "cdcmastery_main", $cfg['db']['port'], $cfg['db']['socket']);

if($oldDB->connect_errno){
	echo $oldDB->error;
	exit();
}

$res = $oldDB->query("SELECT id, name, description FROM groups ORDER BY name ASC");

$roleArray = Array();

if($res->num_rows > 0){
	while($row = $res->fetch_assoc()){
		$uuid = $cdcMastery->genUUID();
		$roleArray[$uuid]['roleName'] = $row['name'];
		$roleArray[$uuid]['roleDescription'] = $row['description'];
		$roleArray[$uuid]['oldID'] = $row['id'];
	}
}

$arrayCount = count($roleArray) + 1;

$res->close();

$stmt = $db->prepare("INSERT INTO roleList (uuid, roleName, roleDescription, oldID) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE uuid=VALUES(uuid),roleName=VALUES(roleName),roleDescription=VALUES(roleDescription),oldID=VALUES(oldID)");

$error = false;
$total=1;

foreach($roleArray as $key => $role){
	$stmt->bind_param("sssi", $key, $role['roleName'], $role['roleDescription'], $role['oldID']);
	
	if(!$stmt->execute()){
		echo "Error inserting role: ".$role['roleName'].". MySQL error: ".$stmt->error."\r\n";
		$error = true;
	}
	else{
		$total++;
	}
}

$stmt->close();

if($error){
	echo "Errors were encountered while migrating roles. " . $total . "/" . $arrayCount . " roles were processed.";
}
else{
	echo "Roles migrated successfully. " . $total . "/" . $arrayCount . " roles were processed.";
}