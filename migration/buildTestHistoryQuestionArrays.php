<?php
define('BASE_PATH', realpath(__DIR__ . '/../'));
define('APP_BASE', realpath(__DIR__ . '/../app'));

include "../includes/bootstrap.inc.php";

$oldDB = new mysqli($cfg['db']['host'], $cfg['db']['user'], $cfg['db']['pass'], "cdcmastery_main", $cfg['db']['port'], $cfg['db']['socket']);

if($oldDB->connect_errno){
	echo $oldDB->error;
	exit();
}

$res = $db->query("SELECT uuid, oldTestID FROM testHistory WHERE questionList IS NULL ORDER BY testTimeStarted ASC");

$testArray = Array();
$arrayCount = $res->num_rows;

if($res->num_rows > 0){
	echo "Found ".$arrayCount." tests to process.\n";
	echo "Building question arrays for testHistory table...\n\n";
	echo "Tests processed: ";
	$i=1;
	$j=1;
	$qstmt = $oldDB->prepare("SELECT DISTINCT(q_uuid) AS q_uuid FROM test_data_migration WHERE t_id = ?");
	while($row = $res->fetch_assoc()){
		
		$qstmt->bind_param("s",$row['oldTestID']);
		
		if($qstmt->execute()){
			$qstmt->bind_result($q_uuid);
			
			while($qstmt->fetch()){
				$questionArray[] = $q_uuid;
			}
			
			if(!empty($questionArray)){
				$testArray[$row['uuid']]['questionList'] = serialize($questionArray);
			}
		}
		else{
			$testArray[$row['uuid']]['questionList'] = NULL;
		}

	
		if($i == 10 || $i == $arrayCount){
			echo "...".$j;
			$i=1;
		}
		else{
			$i++;
		}
		
		$j++;
	}

	$qstmt->close();

	$stmt = $db->prepare("UPDATE testHistory SET questionList = ? WHERE uuid = ?");
		
	$error = false;
	$total=1;
		
	foreach($testArray as $key => $test){
		$stmt->bind_param("ss", $test['questionList'], $key);
			
		if(!$stmt->execute()){
			echo "Error updating test: ".$key.". MySQL error: ".$stmt->error.".\n\nMigration aborted.";
			$error = true;
			$qstmt->close();
			$stmt->close();
			$res->close();
			exit(1);
		}
		else{
			$total++;
		}
	}
	$stmt->close();
	$res->close();
	
	if($error){
		echo "Errors were encountered while updating testHistory table. " . $total . "/" . $arrayCount . " tests were processed.";
	}
	else{
		echo "testHistory updated successfully. " . $total . "/" . $arrayCount . " tests were processed.";
	}
}
else{
	echo "There are no tests to process.";
}