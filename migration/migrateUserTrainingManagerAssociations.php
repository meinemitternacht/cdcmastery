<?php
echo "\n";
define('BASE_PATH', realpath(__DIR__ . '/../'));
define('APP_BASE', realpath(__DIR__ . '/../app'));

include "../includes/bootstrap.inc.php";

$oldDB = new mysqli($cfg['db']['host'], $cfg['db']['user'], $cfg['db']['pass'], "cdcmastery_main", $cfg['db']['port'], $cfg['db']['socket']);

if($oldDB->connect_errno){
	echo $oldDB->error;
	exit();
}

$res = $oldDB->query("SELECT `user_utm_assoc`.`utm`, `user_utm_assoc`.`user`, tUsers.username AS trainingManagerHandle, uUsers.username AS userHandle FROM user_utm_assoc INNER JOIN users AS tUsers ON `user_utm_assoc`.`utm` = tUsers.id INNER JOIN users AS uUsers ON `user_utm_assoc`.`user` = uUsers.id ORDER BY `user_utm_assoc`.`utm` ASC");
$migrationArrayCount = $res->num_rows;
$assocArray = Array();

echo "Building migration array...\n";

if($migrationArrayCount > 0){
	echo "Progress :      ";
	$buildTotal=1;
	while($row = $res->fetch_assoc()){
		$uuid = $cdcMastery->genUUID();
		$trainingManagerUUID = $user->getUUIDByHandle($row['trainingManagerHandle']);
		$userUUID = $user->getUUIDByHandle($row['userHandle']);
		
		if(!empty($trainingManagerUUID) && !empty($userUUID)){
			$assocArray[$uuid]['trainingManagerUUID'] = $trainingManagerUUID; 
			$assocArray[$uuid]['userUUID'] = $userUUID;
		}
		
		$percentDone = intval(($buildTotal / $migrationArrayCount) * 100);
		
		echo "\033[5D";
		echo str_pad($percentDone, 3, ' ', STR_PAD_LEFT) . " %";
		$buildTotal++;
	}

	$arrayCount = count($assocArray) + 1;
	
	$res->close();
	
	$stmt = $db->prepare("INSERT INTO userTrainingManagerAssociations (uuid, trainingManagerUUID, userUUID) VALUES (?,?,?) ON DUPLICATE KEY UPDATE uuid=VALUES(uuid),trainingManagerUUID=VALUES(trainingManagerUUID),userUUID=VALUES(userUUID)");
	
	echo "\nMigrating training manager associations...\n";
	
	$outputArray = Array();
	$error = false;
	$total=0;
	
	echo "Progress :      ";
	foreach($assocArray as $key => $assoc){
		$stmt->bind_param("sss", $key, $assoc['trainingManagerUUID'], $assoc['userUUID']);
		
		if(!$stmt->execute()){
			$outputArray[] = "Error inserting training manager association: ".$assoc['trainingManagerUUID']." => ".$assoc['userUUID'].". MySQL error: ".$stmt->error."\r\n";
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
	echo "No training manager associations found.";
}