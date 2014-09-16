<?php
define('BASE_PATH', realpath(__DIR__ . '/../'));
define('APP_BASE', realpath(__DIR__ . '/../app'));

include "../includes/bootstrap.inc.php";

$oldDB = new mysqli($cfg['db']['host'], $cfg['db']['user'], $cfg['db']['pass'], "cdcmastery_main", $cfg['db']['port'], $cfg['db']['socket']);

if($oldDB->connect_errno){
	echo $oldDB->error;
	exit();
}

$res = $oldDB->query("SELECT username, test_id, score, questions, missed, afsc, time_scored FROM testHistoryMigration LEFT JOIN users ON users.id=testHistoryMigration.user_id ORDER BY testHistoryMigration.test_id ASC");

$testArray = Array();
$arrayCount = $res->num_rows;

echo "Migrating test history...\n\n";
echo "Records processed: ";
$i=1;
$j=1;
if($res->num_rows > 0){
	while($row = $res->fetch_assoc()){
		$userUUID = $user->getUUIDByHandle($row['username']);
		
		if($userUUID){
			$uuid = $cdcMastery->genUUID();
			$testArray[$uuid]['userUUID'] = $userUUID;
			$testArray[$uuid]['afscList'] = serialize($row['afsc']);			
			$testArray[$uuid]['questionList'] = NULL;
			$testArray[$uuid]['totalQuestions'] = $row['questions'];
			$testArray[$uuid]['questionsMissed'] = $row['missed'];
			$testArray[$uuid]['testScore'] = $row['score'];
			$testArray[$uuid]['testTimeStarted'] = date("Y-m-d H:i:s",strtotime(substr($row['test_id'], 0, 14)));
			$testArray[$uuid]['testTimeCompleted'] = $row['time_scored'];
			$testArray[$uuid]['oldTestID'] = $row['test_id'];

		
			if($i == 10 || $i == $arrayCount){
				$stmt = $db->prepare("INSERT INTO testHistory (	uuid,
													userUUID,
													afscList,
													questionList,
													totalQuestions,
													questionsMissed,
													testScore,
													testTimeStarted,
													testTimeCompleted,
													oldTestID)
										VALUES (?,?,?,?,?,?,?,?,?,?)
										ON DUPLICATE KEY UPDATE
													uuid=VALUES(uuid),
													userUUID=VALUES(userUUID),
													afscList=VALUES(afscList),
													questionList=VALUES(questionList),
													totalQuestions=VALUES(totalQuestions),
													questionsMissed=VALUES(questionsMissed),
													testScore=VALUES(testScore),
													testTimeStarted=VALUES(testTimeStarted),
													testTimeCompleted=VALUES(testTimeCompleted),
													oldTestID=VALUES(oldTestID)");
				
				$error = false;
				$total=1;
				
				foreach($testArray as $key => $test){
					$stmt->bind_param("ssssiiisss", $key, $test['userUUID'], $test['afscList'], $test['questionList'], $test['totalQuestions'], $test['questionsMissed'], $test['testScore'], $test['testTimeStarted'], $test['testTimeCompleted'], $test['oldTestID']);
				
					if(!$stmt->execute()){
						echo "Error inserting test: ".$key." (taken on ".$test['testTimeStarted']." by user ".$user->getFullName()."). MySQL error: ".$stmt->error.".\n\nMigration aborted.";
						$error = true;
						$qstmt->close();
						$res->close();
						exit(1);
					}
					else{
						$total++;
					}
				}
				
				$testArray = Array();
				$stmt->free_result();
				
				echo "...".$j;
				$i=1;
			}
			else{
				$i++;
			}
			
			$j++;
		}
	}
}

$qstmt->close();
$res->close();
$stmt->close();

if($error){
	echo "Errors were encountered while migrating tests. " . $total . "/" . $arrayCount . " tests were processed.";
}
else{
	echo "Tests migrated successfully. " . $total . "/" . $arrayCount . " tests were processed.";
}