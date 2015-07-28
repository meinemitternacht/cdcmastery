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

$res = $db->query("SELECT uuid, oldTestID FROM testHistory ORDER BY oldTestID ASC");

$dataArray = Array();

echo "Migrating test data...\n\n";
echo "Rows processed: ";

$rowCount=0;

$continue = true;

if($res->num_rows > 0){
	$td_stmt = $oldDB->prepare("SELECT q_uuid, a_uuid FROM test_data WHERE t_id = ?");
    $i=0;
	while($row = $res->fetch_assoc()){
        if($continue) {
            $td_stmt->bind_param("s", $row['oldTestID']);
            $td_stmt->bind_result($questionUUID, $answerUUID);

            if ($td_stmt->execute()) {
                while ($td_stmt->fetch()) {
                    $dataArray[$i]['testUUID'] = $row['uuid'];
                    $dataArray[$i]['questionUUID'] = $questionUUID;
                    $dataArray[$i]['answerUUID'] = $answerUUID;
                    $rowCount++;
                    $i++;
                }
            }

            if (count($dataArray) >= 500) {
                $qry = "INSERT INTO cdcmastery_dev.testData (testUUID, questionUUID, answerUUID) VALUES";

                $first = true;
                foreach ($dataArray as $key => $data) {
                    if ($first == false) {
                        $qry .= ",";
                    }

                    $qry .= " ('" . $data['testUUID'] . "','" . $data['questionUUID'] . "','" . $data['answerUUID'] . "')";
                    $first = false;
                }

                if (!$db->query($qry)) {
                    echo "There was a problem inserting the data. " . $db->error . "\n\n";
                    echo " ***** QUERY ***** \n\n" . $qry . "\n\n ***** QUERY *****\n\n";
                    echo " ***** VAR_DUMP ***** \n\n" . var_dump($dataArray) . "\n\n ***** VAR_DUMP *****";
                    $continue = false;
                } else {
                    $dataArray = Array();
                    $i=0;
                    echo "..." . $rowCount;
                }
            }
        }
	}
	
	if(!empty($dataArray)){
		$qry = "INSERT INTO cdcmastery_dev.testData (testUUID, questionUUID, answerUUID) VALUES";
			
		$first = true;
		foreach($dataArray as $key => $data){
			if($first == false){
				$qry .= ",";
			}

            $qry .= " ('" . $data['testUUID'] . "','" . $data['questionUUID'] . "','" . $data['answerUUID'] . "')";
			$first = false;
		}
			
		if(!$db->query($qry)){
			echo "There was a problem inserting the data. ".$db->error."\n\n";
			exit(1);
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