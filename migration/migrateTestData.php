<?php
define('BASE_PATH', realpath(__DIR__ . '/../'));
define('APP_BASE', realpath(__DIR__ . '/../app'));

include "../includes/bootstrap.inc.php";

$oldDB = new mysqli($cfg['db']['host'], $cfg['db']['user'], $cfg['db']['pass'], "cdcmastery_main", $cfg['db']['port'], $cfg['db']['socket']);

if($oldDB->connect_errno){
	echo $oldDB->error;
	exit();
}

$res = $db->query("SELECT uuid, oldTestID FROM testHistory ORDER BY oldTestID ASC");

$dataArray = Array();

echo "Migrating test data...\n\n";
echo "Rows processed: ";

$i=1;
$rowCount=0;

if($res->num_rows > 0){
	$td_stmt = $oldDB->prepare("SELECT q_uuid, a_uuid FROM test_data_migration WHERE t_id = ?");
	while($row = $res->fetch_assoc()){
		$td_stmt->bind_param("s",$row['oldTestID']);
		$td_stmt->bind_result($questionUUID, $answerUUID);
		
		if($td_stmt->execute()){
			while($td_stmt->fetch()){
				$uuid = $cdcMastery->genUUID();
				$dataArray[$uuid]['testUUID'] = $row['uuid'];
				$dataArray[$uuid]['questionUUID'] = $questionUUID;
				$dataArray[$uuid]['answerUUID'] = $answerUUID;
				$rowCount++;
			}
		}
		
		if(count($dataArray) >= 500){
			$qry = "INSERT INTO cdcmastery_dev.testData (uuid, testUUID, questionUUID, answerUUID) VALUES";
			
			$first = true;
			foreach($dataArray as $key => $data){
				if($first == false){
					$qry .= ",";
				}
				
				$qry .= " ('".$key."','".implode("','",$data)."')";
				$first = false;
			}
			
			if(!$db->query($qry)){
				echo "There was a problem inserting the data. ".$db->error."\n\n";
				break;
			}
			else{
				unset($dataArray);
				$dataArray = Array();
				echo "...".$rowCount;
			}
		}
	}
	
	if(!empty($dataArray)){
		$qry = "INSERT INTO cdcmastery_dev.testData (uuid, testUUID, questionUUID, answerUUID) VALUES";
			
		$first = true;
		foreach($dataArray as $key => $data){
			if($first == false){
				$qry .= ",";
			}
		
			$qry .= " ('".$key."','".implode("','",$data)."')";
			$first = false;
		}
			
		if(!$db->query($qry)){
			echo "There was a problem inserting the data. ".$db->error."\n\n";
			break;
		}
		else{
			unset($dataArray);
			$dataArray = Array();
			echo "...".$rowCount;
		}
	}
	
	$td_stmt->close();
}
else{
	echo "There were no tests to migrate data for.\n";
}

$res->close();