<?php
define('BASE_PATH', realpath(__DIR__ . '/../'));
define('APP_BASE', realpath(__DIR__ . '/../app'));

include "../includes/bootstrap.inc.php";

$res = $db->query("SELECT uuid, afscList FROM testHistoryTest ORDER BY testTimeStarted ASC");
$migrationArrayCount = $res->num_rows;
$mappingArray = Array();

echo "Building new AFSC Mappings...\n";

if($migrationArrayCount > 0){
	echo "Progress :      ";
	$buildTotal=1;
	while($row = $res->fetch_assoc()){
		$afscString = unserialize($row['afscList']);
		
		$newID = $afsc->getMigratedAFSCUUID($afscString);
		
		if($newID){
			$mappingArray[$row['uuid']][] = $newID;
		}

		$percentDone = intval(($buildTotal / $migrationArrayCount) * 100);
		
		echo "\033[5D";
		echo str_pad($percentDone, 3, ' ', STR_PAD_LEFT) . " %";
		$buildTotal++;
	}

	$arrayCount = count($mappingArray) + 1;
	
	$res->close();
	
	$stmt = $db->prepare("UPDATE testHistoryTest SET afscList = ? WHERE uuid = ?");
	
	echo "\nMigrating AFSC mappings...\n";
	
	$outputArray = Array();
	$error = false;
	$total=0;
	
	echo "Progress :      ";
	foreach($mappingArray as $key => $newAFSCArray){
		$serializedList = serialize($newAFSCArray);
		$stmt->bind_param("ss", $serializedList, $key);
		
		if(!$stmt->execute()){
			$outputArray[] = "Error migrating AFSC mapping: ".$key." => ".$serializedList.". MySQL error: ".$stmt->error."\r\n";
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
	echo "No tests found in the database.";
}