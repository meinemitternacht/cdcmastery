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

echo "Building migration array for normal questions...\n";

$res = $oldDB->query("SELECT `cdc_questions`.`uuid`, `cdc_questions`.`question`, `cdc_questions`.`afsc` FROM cdc_questions ORDER BY `cdc_questions`.`afsc` ASC");
$normQuestionCount = $res->num_rows;
$assocArray = Array();

if($normQuestionCount > 0){
	echo "Progress :      ";
	$buildTotal=1;
	while($row = $res->fetch_assoc()){
		$afscUUID = $afscManager->getMigratedAFSCUUID($row['afsc']);
		
		if(!empty($afscUUID)){
			$normalQuestionArray[$row['uuid']]['questionText'] = $row['question'];
			$normalQuestionArray[$row['uuid']]['afscUUID'] = $afscUUID;
		}
		
		$percentDone = intval(($buildTotal / $normQuestionCount) * 100);
		
		echo "\033[5D";
		echo str_pad($percentDone, 3, ' ', STR_PAD_LEFT) . " %";
		$buildTotal++;
	}

	$arrayCount = count($normalQuestionArray) + 1;
	
	$res->close();
	
	$stmt = $db->prepare("INSERT INTO questionData (uuid, afscUUID, questionText) VALUES (?,?,?) ON DUPLICATE KEY UPDATE uuid=VALUES(uuid),afscUUID=VALUES(afscUUID),questionText=VALUES(questionText)");
	
	echo "\nMigrating normal questions...\n";
	
	$outputArray = Array();
	$error = false;
	$total=0;
	
	echo "Progress :      ";
	foreach($normalQuestionArray as $key => $normalQuestion){
		$stmt->bind_param("sss", $key, $normalQuestion['afscUUID'], $normalQuestion['questionText']);
		
		if(!$stmt->execute()){
			$outputArray[] = "Error inserting question: ".$key." => ".addslashes($normalQuestion['questionText']).". MySQL error: ".$stmt->error."\r\n";
			$error = true;
		}
		
		$percentDone = intval(($total / $arrayCount) * 100);
		
		echo "\033[5D";
		echo str_pad($percentDone, 3, ' ', STR_PAD_LEFT) . " %";
		$total++;
	}
	
	$stmt->close();
	
	echo "\nNormal question migration complete.\n";
	
	if(!empty($outputArray)){
		echo "Results:\n";
		foreach($outputArray as $outputLine){
			echo $outputLine."\n";
		}
	}
}
else{
	echo "No normal questions found.";
}

echo "Building migration array for normal answers...\n";

$res = $oldDB->query("SELECT `cdc_answers`.`uuid`, `cdc_answers`.`q_uuid`, `cdc_answers`.`answer`, `cdc_answers`.`correct` FROM cdc_answers ORDER BY `cdc_answers`.`q_uuid` ASC");
$normAnswerCount = $res->num_rows;
$assocArray = Array();

if($normAnswerCount > 0){
	echo "Progress :      ";
	$buildTotal=1;
	while($row = $res->fetch_assoc()){
		$normalAnswerArray[$row['uuid']]['answerText'] = $row['answer'];
		$normalAnswerArray[$row['uuid']]['answerCorrect'] = $row['correct'];
		$normalAnswerArray[$row['uuid']]['questionUUID'] = $row['q_uuid'];

		$percentDone = intval(($buildTotal / $normAnswerCount) * 100);

		echo "\033[5D";
		echo str_pad($percentDone, 3, ' ', STR_PAD_LEFT) . " %";
		$buildTotal++;
	}

	$arrayCount = count($normalAnswerArray) + 1;

	$res->close();

	$stmt = $db->prepare("INSERT INTO answerData (uuid, answerText, answerCorrect, questionUUID) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE uuid=VALUES(uuid),answerText=VALUES(answerText),answerCorrect=VALUES(answerCorrect),questionUUID=VALUES(questionUUID)");

	echo "\nMigrating normal answers...\n";

	$outputArray = Array();
	$error = false;
	$total=0;

	echo "Progress :      ";
	foreach($normalAnswerArray as $key => $normalAnswer){
		$stmt->bind_param("ssis", $key, $normalAnswer['answerText'], $normalAnswer['answerCorrect'], $normalAnswer['questionUUID']);

		if(!$stmt->execute()){
			$outputArray[] = "Error inserting answer: ".$key." => ".addslashes($normalAnswer['answerText']).". MySQL error: ".$stmt->error."\r\n";
			$error = true;
		}

		$percentDone = intval(($total / $arrayCount) * 100);

		echo "\033[5D";
		echo str_pad($percentDone, 3, ' ', STR_PAD_LEFT) . " %";
		$total++;
	}

	$stmt->close();

	echo "\nNormal answer migration complete.\n";

	if(!empty($outputArray)){
		echo "Results:\n";
		foreach($outputArray as $outputLine){
			echo $outputLine."\n";
		}
	}
}
else{
	echo "No normal answers found.";
}

echo "Building migration array for FOUO questions...\n";

$res = $oldDB->query("SELECT `cdc_questions_fouo`.`uuid`, AES_DECRYPT(`cdc_questions_fouo`.`question`,'".$cdcMastery->getEncryptionKey()."') AS question, `cdc_questions_fouo`.`afsc` FROM cdc_questions_fouo ORDER BY `cdc_questions_fouo`.`afsc` ASC");
$fouoQuestionCount = $res->num_rows;
$assocArray = Array();

if($fouoQuestionCount > 0){
	echo "Progress :      ";
	$buildTotal=1;
	while($row = $res->fetch_assoc()){
		$afscUUID = $afscManager->getMigratedAFSCUUID($row['afsc']);

		if(!empty($afscUUID)){
			$fouoQuestionArray[$row['uuid']]['questionText'] = $row['question'];
			$fouoQuestionArray[$row['uuid']]['afscUUID'] = $afscUUID;
		}

		$percentDone = intval(($buildTotal / $fouoQuestionCount) * 100);

		echo "\033[5D";
		echo str_pad($percentDone, 3, ' ', STR_PAD_LEFT) . " %";
		$buildTotal++;
	}

	$arrayCount = count($fouoQuestionArray) + 1;

	$res->close();

	$stmt = $db->prepare("INSERT INTO questionData (uuid, afscUUID, questionText) VALUES (?,?,AES_ENCRYPT(?,'".$cdcMastery->getEncryptionKey()."'))
											ON DUPLICATE KEY UPDATE uuid=VALUES(uuid),
																	afscUUID=VALUES(afscUUID),
																	questionText=AES_ENCRYPT(VALUES(questionText),'".$cdcMastery->getEncryptionKey()."')");

	echo "\nMigrating FOUO questions...\n";

	$outputArray = Array();
	$error = false;
	$total=0;

	echo "Progress :      ";
	foreach($fouoQuestionArray as $key => $fouoQuestion){
		$stmt->bind_param("sss", $key, $fouoQuestion['afscUUID'], $fouoQuestion['questionText']);

		if(!$stmt->execute()){
			$outputArray[] = "Error inserting question: ".$key." => ".addslashes($fouoQuestion['questionText']).". MySQL error: ".$stmt->error."\r\n";
			$error = true;
		}

		$percentDone = intval(($total / $arrayCount) * 100);

		echo "\033[5D";
		echo str_pad($percentDone, 3, ' ', STR_PAD_LEFT) . " %";
		$total++;
	}

	$stmt->close();

	echo "\nFOUO question migration complete.\n";

	if(!empty($outputArray)){
		echo "Results:\n";
		foreach($outputArray as $outputLine){
			echo $outputLine."\n";
		}
	}
}
else{
	echo "No FOUO questions found.";
}

echo "Building migration array for FOUO answers...\n";

$res = $oldDB->query("SELECT `cdc_answers_fouo`.`uuid`, `cdc_answers_fouo`.`q_uuid`, AES_DECRYPT(`cdc_answers_fouo`.`answer`,'".$cdcMastery->getEncryptionKey()."') AS answer, `cdc_answers_fouo`.`correct` FROM cdc_answers_fouo ORDER BY `cdc_answers_fouo`.`q_uuid` ASC");
$fouoAnswerCount = $res->num_rows;
$assocArray = Array();

if($fouoAnswerCount > 0){
	echo "Progress :      ";
	$buildTotal=1;
	while($row = $res->fetch_assoc()){
		$fouoAnswerArray[$row['uuid']]['answerText'] = $row['answer'];
		$fouoAnswerArray[$row['uuid']]['answerCorrect'] = $row['correct'];
		$fouoAnswerArray[$row['uuid']]['questionUUID'] = $row['q_uuid'];

		$percentDone = intval(($buildTotal / $fouoAnswerCount) * 100);

		echo "\033[5D";
		echo str_pad($percentDone, 3, ' ', STR_PAD_LEFT) . " %";
		$buildTotal++;
	}

	$arrayCount = count($fouoAnswerArray) + 1;

	$res->close();

	$stmt = $db->prepare("INSERT INTO answerData (uuid, answerText, answerCorrect, questionUUID) VALUES (?,AES_ENCRYPT(?,'".$cdcMastery->getEncryptionKey()."'),?,?)
											ON DUPLICATE KEY UPDATE uuid=VALUES(uuid),
																	answerText=AES_ENCRYPT(VALUES(answerText),'".$cdcMastery->getEncryptionKey()."'),
																	answerCorrect=VALUES(answerCorrect),
																	questionUUID=VALUES(questionUUID)");

	echo "\nMigrating FOUO answers...\n";

	$outputArray = Array();
	$error = false;
	$total=0;

	echo "Progress :      ";
	foreach($fouoAnswerArray as $key => $fouoAnswer){
		$stmt->bind_param("ssis", $key, $fouoAnswer['answerText'], $fouoAnswer['answerCorrect'], $fouoAnswer['questionUUID']);

		if(!$stmt->execute()){
			$outputArray[] = "Error inserting answer: ".$key." => ".addslashes($fouoAnswer['answerText']).". MySQL error: ".$stmt->error."\r\n";
			$error = true;
		}

		$percentDone = intval(($total / $arrayCount) * 100);

		echo "\033[5D";
		echo str_pad($percentDone, 3, ' ', STR_PAD_LEFT) . " %";
		$total++;
	}

	$stmt->close();

	echo "\nFOUO answer migration complete.\n";

	if(!empty($outputArray)){
		echo "Results:\n";
		foreach($outputArray as $outputLine){
			echo $outputLine."\n";
		}
	}
}
else{
	echo "No FOUO answers found.";
}