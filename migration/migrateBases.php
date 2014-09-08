<?php
define('BASE_PATH', realpath(__DIR__ . '/../'));
define('APP_BASE', realpath(__DIR__ . '/../app'));

include "../includes/bootstrap.inc.php";

$oldDB = new mysqli($cfg['db']['host'], $cfg['db']['user'], $cfg['db']['pass'], "cdcmastery_main", $cfg['db']['port'], $cfg['db']['socket']);

if($oldDB->connect_errno){
	echo $oldDB->error;
	exit();
}

$res = $oldDB->query("SELECT id, base_name FROM bases ORDER BY base_name ASC");

$baseArray = Array();

if($res->num_rows > 0){
	while($row = $res->fetch_assoc()){
		$uuid = $cdcMastery->genUUID();
		$baseArray[$uuid]['baseName'] = $row['base_name'];
		$baseArray[$uuid]['oldID'] = $row['id'];
	}
}

$arrayCount = count($baseArray);

$res->close();

$stmt = $db->prepare("INSERT INTO baseList (uuid, baseName, oldID) VALUES (?,?,?) ON DUPLICATE KEY UPDATE uuid=VALUES(uuid),baseName=VALUES(baseName),oldID=VALUES(oldID)");

$error = false;
$total=1;

foreach($baseArray as $key => $base){
	$stmt->bind_param("ssi", $key, $base['baseName'], $base['oldID']);
	
	if(!$stmt->execute()){
		echo "Error inserting base: ".$base['baseName'].". MySQL error: ".$stmt->error."\r\n";
		$error = true;
	}
	else{
		$total++;
	}
}

$stmt->close();

if($error){
	echo "Errors were encountered while migrating bases. " . $total . "/" . $arrayCount . " bases were processed.";
}
else{
	echo "Bases migrated successfully. " . $total . "/" . $arrayCount . " bases were processed.";
}