<?php
/**
 * Created by PhpStorm.
 * User: Claude Bing
 * Date: 9/30/2015
 * Time: 1:32 AM
 */

$testManager = new CDCMastery\TestManager($db, $systemLog, $afscManager);

$incompleteTestList = $testManager->listIncompleteTests(true);

foreach($incompleteTestList as $incompleteTestUUID){
    $testManager->loadIncompleteTest($incompleteTestUUID);
    $stmt = $db->prepare("SELECT COUNT(*) AS count FROM testData WHERE testUUID = ? ");
    $stmt->bind_param("s",$incompleteTestUUID);
    $stmt->execute();
    $stmt->bind_result($testDataCount);
    $stmt->fetch();
    $stmt->close();
    if($testManager->getIncompleteQuestionsAnswered() != $testDataCount) {
        $deleteTestUUID[] = $incompleteTestUUID;

        echo "testUUID: " . $systemLog->formatDetailData($incompleteTestUUID);
        echo "<br>";
        echo "Questions Answered: " . $testManager->getIncompleteQuestionsAnswered();
        echo "<br>";
        echo "testData Count:" . $testDataCount;
        echo "<br>";
    }
}

/*
echo count($deleteTestUUID);

foreach($deleteTestUUID as $dTestUUID){
    $testManager->deleteIncompleteTest(false,$dTestUUID,false,false);
}*/