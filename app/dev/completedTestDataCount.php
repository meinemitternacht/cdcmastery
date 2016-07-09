<?php
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 9/30/2015
 * Time: 3:20 AM
 */

$testManager = new CDCMastery\TestManager($db, $systemLog, $afscManager);

$stmt = $db->prepare("SELECT uuid FROM `testHistory` WHERE (testScore + questionsMissed) != totalQuestions ORDER BY `testHistory`.`testTimeStarted` DESC");
$stmt->execute();
$stmt->bind_result($testUUID);

while($stmt->fetch()){
    $testUUIDList[] = $testUUID;
}

$stmt->close();

foreach($testUUIDList as $completedTestUUID){
    $testManager->loadTest($completedTestUUID);
    echo "testUUID: ".$systemLog->formatDetailData($completedTestUUID);
    echo "<br>";
    echo "Test Score: ".$testManager->getTestScore();
    echo "<br>";
    echo "Questions Missed: ".$testManager->getQuestionsMissed();
    echo "<br>";
    echo "Total Questions: ".$testManager->getTotalQuestions();
    echo "<br>";

    $stmt = $db->prepare("SELECT COUNT(*) AS count FROM testData WHERE testUUID = ? ");
    $stmt->bind_param("s",$completedTestUUID);
    $stmt->execute();
    $stmt->bind_result($testDataCount);
    $stmt->fetch();
    $stmt->close();

    if($testDataCount < 99){
        $deleteTestUUID[] = $completedTestUUID;
    }

    echo "testData Count: ".$testDataCount;
    echo "<br>";
}

/*
echo count($deleteTestUUID);

foreach($deleteTestUUID as $dTestUUID){
    $testManager->deleteTest($dTestUUID);
}*/