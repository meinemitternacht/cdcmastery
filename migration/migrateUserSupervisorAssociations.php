<?php
define('BASE_PATH', realpath(__DIR__ . '/../'));
define('APP_BASE', realpath(__DIR__ . '/../app'));

include "../includes/bootstrap.inc.php";

$oldDB = new mysqli($cfg['db']['host'], $cfg['db']['user'], $cfg['db']['pass'], "cdcmastery_main", $cfg['db']['port'], $cfg['db']['socket']);

if($oldDB->connect_errno){
	echo $oldDB->error;
	exit();
}

$res = $oldDB->query("SELECT s_id, u_id, sUsers.username AS supervisorHandle, uUsers.username AS userHandle FROM user_super_assoc INNER JOIN users AS sUsers ON s_id = sUsers.id INNER JOIN users AS uUsers ON u_id = uUsers.id ORDER BY s_id ASC");
$migrationArrayCount = $res->num_rows;
$assocArray = Array();

echo "Building migration array...\n";

if($migrationArrayCount > 0){
	echo "Progress :      ";
	$buildTotal=1;
	while($row = $res->fetch_assoc()){
		$uuid = $cdcMastery->genUUID();
		$supervisorUUID = $user->getUUIDByHandle($row['supervisorHandle']);
		$userUUID = $user->getUUIDByHandle($row['userHandle']);
		
		if(!empty($supervisorUUID) && !empty($userUUID)){
			$assocArray[$uuid]['supervisorUUID'] = $supervisorUUID; 
			$assocArray[$uuid]['userUUID'] = $userUUID;
		}
		
		$percentDone = intval(($buildTotal / $migrationArrayCount) * 100);
		
		echo "\033[5D";
		echo str_pad($percentDone, 3, ' ', STR_PAD_LEFT) . " %";
		$buildTotal++;
	}

	$arrayCount = count($assocArray) + 1;
	
	$res->close();
	
	$stmt = $db->prepare("INSERT INTO userSupervisorAssociations (uuid, supervisorUUID, userUUID) VALUES (?,?,?) ON DUPLICATE KEY UPDATE uuid=VALUES(uuid),supervisorUUID=VALUES(supervisorUUID),userUUID=VALUES(userUUID)");
	
	echo "\nMigrating supervisor associations...\n";
	
	$outputArray = Array();
	$error = false;
	$total=0;
	
	echo "Progress :      ";
	foreach($assocArray as $key => $assoc){
		$stmt->bind_param("sss", $key, $assoc['supervisorUUID'], $assoc['userUUID']);
		
		if(!$stmt->execute()){
			$outputArray[] = "Error inserting supervisor association: ".$assoc['supervisorUUID']." => ".$assoc['userUUID'].". MySQL error: ".$stmt->error."\r\n";
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
	echo "No supervisor associations found.";
}