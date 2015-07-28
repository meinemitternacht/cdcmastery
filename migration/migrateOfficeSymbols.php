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

$res = $oldDB->query("SELECT uuid, officeSymbol FROM office_symbols ORDER BY officeSymbol ASC");

$osArray = Array();

if($res->num_rows > 0){
	while($row = $res->fetch_assoc()){
		$osArray[$row['uuid']]['officeSymbol'] = $row['officeSymbol'];
	}
}

$arrayCount = count($osArray) + 1;

$res->close();

$stmt = $db->prepare("INSERT INTO officeSymbolList (uuid, officeSymbol) VALUES (?,?) ON DUPLICATE KEY UPDATE uuid=VALUES(uuid),officeSymbol=VALUES(officeSymbol)");

$error = false;
$total=1;

foreach($osArray as $key => $os){
	$stmt->bind_param("ss", $key, $os['officeSymbol']);
	
	if(!$stmt->execute()){
		echo "Error inserting office symbol: ".$os['officeSymbol'].". MySQL error: ".$stmt->error."\r\n";
		$error = true;
	}
	else{
		$total++;
	}
}

$stmt->close();

if($error){
	echo "Errors were encountered while migrating office symbols. " . $total . "/" . $arrayCount . " office symbols were processed.";
}
else{
	echo "Office symbols migrated successfully. " . $total . "/" . $arrayCount . " office symbols were processed.";
}