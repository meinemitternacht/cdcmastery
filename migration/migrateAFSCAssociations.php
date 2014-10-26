<?php
define('BASE_PATH', realpath(__DIR__ . '/../'));
define('APP_BASE', realpath(__DIR__ . '/../app'));

include "../includes/bootstrap.inc.php";

$oldDB = new mysqli($cfg['db']['host'], $cfg['db']['user'], $cfg['db']['pass'], "cdcmastery_main", $cfg['db']['port'], $cfg['db']['socket']);

if($oldDB->connect_errno){
	echo $oldDB->error;
	exit();
}

$res = $oldDB->query("SELECT `user_auth_afsc`.`afsc_id`, `users`.`username` AS userHandle FROM user_auth_afsc INNER JOIN users ON `user_auth_afsc`.`u_id` = `users`.`id` ORDER BY `user_auth_afsc`.`u_id` ASC");
$migrationArrayCount = $res->num_rows;
$assocArray = Array();

echo "Building migration array...\n";

if($migrationArrayCount > 0){
	echo "Progress :      ";
	$buildTotal=1;
	while($row = $res->fetch_assoc()){
		$uuid = $cdcMastery->genUUID();
		$userUUID = $user->getUUIDByHandle($row['userHandle']);
		$afscUUID = $afsc->getMigratedAFSCUUID($row['afsc_id']);
		
		if(!empty($userUUID) && !empty($afscUUID)){
			$assocArray[$uuid]['userUUID'] = $userUUID;
			$assocArray[$uuid]['afscUUID'] = $afscUUID;
			$assocArray[$uuid]['userAuthorized'] = true;
		}
		
		$percentDone = intval(($buildTotal / $migrationArrayCount) * 100);
		
		echo "\033[5D";
		echo str_pad($percentDone, 3, ' ', STR_PAD_LEFT) . " %";
		$buildTotal++;
	}

	$arrayCount = count($assocArray) + 1;
	
	$res->close();
	
	$stmt = $db->prepare("INSERT INTO userAFSCAssociations (uuid, userUUID, afscUUID, userAuthorized) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE uuid=VALUES(uuid),userUUID=VALUES(userUUID),afscUUID=VALUES(afscUUID),userAuthorized=VALUES(userAuthorized)");
	
	echo "\nMigrating AFSC associations...\n";
	
	$outputArray = Array();
	$error = false;
	$total=0;
	
	echo "Progress :      ";
	foreach($assocArray as $key => $assoc){
		$stmt->bind_param("sssi", $key, $assoc['userUUID'], $assoc['afscUUID'], $assoc['userAuthorized']);
		
		if(!$stmt->execute()){
			$outputArray[] = "Error inserting AFSC association: ".$assoc['userUUID']." => ".$assoc['afscUUID'].". MySQL error: ".$stmt->error."\r\n";
			$error = true;
		}
		
		$percentDone = intval(($total / $arrayCount) * 100);
		
		echo "\033[5D";
		echo str_pad($percentDone, 3, ' ', STR_PAD_LEFT) . " %";
		$total++;
	}
	
	$stmt->close();
	
	echo "\nMigration complete.\n";
	
	if(!empty($outputArray)){
		echo "Results:\n";
		foreach($outputArray as $outputLine){
			echo $outputLine."\n";
		}
	}
}
else{
	echo "No AFSC associations found.";
}