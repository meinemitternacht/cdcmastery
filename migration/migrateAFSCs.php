<?php
define('BASE_PATH', realpath(__DIR__ . '/../'));
define('APP_BASE', realpath(__DIR__ . '/../app'));

include "../includes/bootstrap.inc.php";

$oldDB = new mysqli($cfg['db']['host'], $cfg['db']['user'], $cfg['db']['pass'], "cdcmastery_main", $cfg['db']['port'], $cfg['db']['socket']);

if($oldDB->connect_errno){
	echo $oldDB->error;
	exit();
}

$res = $oldDB->query("SELECT `cdc_afsc`.`id`, `cdc_afsc`.`afscName`, `cdc_afsc`.`version`, `cdc_afsc`.`hidden`, `cdc_afsc`.`fouo` FROM cdc_afsc ORDER BY afscName ASC");
$migrationArrayCount = $res->num_rows;
$afscArray = Array();

echo "Building migration array...\n";

if($migrationArrayCount > 0){
	echo "Progress :      ";
	$buildTotal=1;
	while($row = $res->fetch_assoc()){
		$uuid = $cdcMastery->genUUID();
		$afscArray[$uuid]['afscName'] = $row['afscName'];
		$afscArray[$uuid]['afscVersion'] = $row['version'];
		$afscArray[$uuid]['afscHidden'] = $row['hidden'];
		$afscArray[$uuid]['afscFOUO'] = $row['fouo'];
		$afscArray[$uuid]['oldID'] = $row['id'];
		
		$percentDone = intval(($buildTotal / $migrationArrayCount) * 100);
		
		echo "\033[5D";
		echo str_pad($percentDone, 3, ' ', STR_PAD_LEFT) . " %";
		$buildTotal++;
	}

	$arrayCount = count($afscArray) + 1;
	
	$res->close();
	
	$stmt = $db->prepare("INSERT INTO afscList (uuid, afscName, afscVersion, afscFOUO, afscHidden, oldID) VALUES (?,?,?,?,?,?) 
							ON DUPLICATE KEY UPDATE uuid=VALUES(uuid),
													afscName=VALUES(afscName),
													afscVersion=VALUES(afscVersion),
													afscFOUO=VALUES(afscFOUO),
													afscHidden=VALUES(afscHidden),
													oldID=VALUES(oldID)");
	
	echo "\nMigrating AFSCs...\n";
	
	$outputArray = Array();
	$error = false;
	$total=0;
	
	echo "Progress :      ";
	foreach($afscArray as $key => $assoc){
		$stmt->bind_param("sssiii", $key, $assoc['afscName'], $assoc['afscVersion'], $assoc['afscFOUO'], $assoc['afscHidden'], $assoc['oldID']);
		
		if(!$stmt->execute()){
			$outputArray[] = "Error inserting AFSC: ".$assoc['afscName'].". MySQL error: ".$stmt->error."\r\n";
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
	echo "No AFSCs found.";
}