<?php
define('BASE_PATH', realpath(__DIR__ . '/../'));
define('APP_BASE', realpath(__DIR__ . '/../app'));

include "../includes/bootstrap.inc.php";

$res = $db->query("SELECT uuid FROM testHistory ORDER BY testTimeStarted ASC");
$totalTests = $res->num_rows;

while($row = $res->fetch_assoc()){
	$testArray[] = $row['uuid'];
}

$res->close();

$stmt = $db->prepare("SELECT questionUUID FROM testData WHERE testUUID = ?");

echo "Scanning testData table for duplicate questions...\n";

$outputArray = Array();

echo "Progress :      ";  // 5 characters of padding at the end
$testCount = 0;

foreach($testArray as $test){
	$stmt->bind_param("s",$test);
	$stmt->bind_result($questionUUID);
	
	$questionList = Array();
	
	if($stmt->execute()){
		while($stmt->fetch()){
			if(in_array($questionUUID,$questionList)){
				$outputArray[] = "Found duplicate question in test ".$test.": ".$questionUUID." (testData key ".$test . $questionUUID.")";
				$testIDArray[] = $test;
                $deleteRowArray[] = "DELETE FROM testData WHERE testUUID = '".$test."' AND questionUUID = '".$questionUUID."' LIMIT 1";
			}
			else{
				$questionList[] = $questionUUID;
			}
		}
	}

	$testCount++;
	$percentDone = intval(($testCount / $totalTests) * 100);
	
	echo "\033[5D";      // Move 5 characters backward
	echo str_pad($percentDone, 3, ' ', STR_PAD_LEFT) . " %";    // Output is always 5 characters long
}

echo "\nScan complete.\n";

if(!empty($outputArray)){
	echo "Results:\n";
	foreach($outputArray as $outputLine){
		echo $outputLine."\n";
	}
	
	echo "\nEZ MySQL query to find the tests! : SELECT * FROM testHistory WHERE uuid IN ('".implode("','",$testIDArray)."') ORDER BY testTimeStarted ASC\n";
	echo "\nEZ MySQL query to delete the culprits! : ";
    foreach($deleteRowArray as $deleteRow){
        echo $deleteRow . "\n";
    }
}
else{
	echo "No duplicate questions found.";
}
