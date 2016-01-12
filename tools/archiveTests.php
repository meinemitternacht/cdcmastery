<?php
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 1/8/2016
 * Time: 5:19 PM
 */
define('BASE_PATH', realpath(__DIR__ . '/../'));
define('APP_BASE', realpath(__DIR__ . '/../app'));

include "../includes/bootstrap.inc.php";

$testManager = new testManager($db, $log, $afsc);
$answerManager = new answerManager($db,$log);
$questionManager = new questionManager($db,$log,$afsc,$answerManager);

$testArray = $testManager->listArchivableTests();
$totalTests = count($testArray);

if($totalTests > 0) {
    echo "Archivable Tests: " . number_format($totalTests) . "\n";
    echo "Progress :      ";
    $testCount = 0;
    foreach ($testArray as $testUUID) {
        if ($testManager->loadTest($testUUID)) {
            $rawAFSCList = $testManager->getAFSCList();

            foreach ($rawAFSCList as $key => $val) {
                $rawAFSCList[$key] = $afsc->getAFSCName($val);
            }

            if (count($rawAFSCList) > 1) {
                $testAFSCList = implode(",", $rawAFSCList);
            } else {
                $testAFSCList = $rawAFSCList[0];
            }

            $xml = new SimpleXMLElement('<xml/>');
            $testDetails = $xml->addChild('testDetails');
            $testDetails->addChild('timeStarted', $testManager->getTestTimeStarted());
            $testDetails->addChild('timeCompleted', $testManager->getTestTimeCompleted());
            $testDetails->addChild('afscList', $testAFSCList);
            $testDetails->addChild('totalQuestions', $testManager->getTotalQuestions());
            $testDetails->addChild('questionsMissed', $testManager->getQuestionsMissed());
            $testDetails->addChild('testScore', $testManager->getTestScore());

            $testManager->loadTestData($testUUID);
            $testData = $testManager->getTestData();

            if (!empty($testData) && is_array($testData)) {
                foreach ($testData as $questionUUID => $answerUUID) {
                    if ($questionManager->loadQuestion($questionUUID)) {
                        $question = $xml->addChild('question');
                        $answerManager->setFOUO($questionManager->queryQuestionFOUO($questionUUID));
                        $answerManager->loadAnswer($answerUUID);
                        $question->addChild('questionText', htmlspecialchars($questionManager->getQuestionText()));
                        if ($answerManager->getAnswerCorrect()) {
                            $question->addChild('answerGiven', htmlspecialchars($answerManager->getAnswerText()));
                            $question->addChild('answerCorrect', 'yes');
                        } else {
                            $question->addChild('answerGiven', htmlspecialchars($answerManager->getAnswerText()));
                            $question->addChild('answerCorrect', 'no');
                        }
                    } else {
                        $archivedText = $questionManager->getArchivedQuestionText($questionUUID);
                        if ($archivedText) {
                            $question = $xml->addChild('question');
                            $question->addChild('questionText', htmlspecialchars($archivedText));

                            $answerManager->loadArchivedAnswer($answerUUID);

                            if ($answerManager->getAnswerCorrect()) {
                                $question->addChild('answerGiven', htmlspecialchars($answerManager->getAnswerText()));
                                $question->addChild('answerCorrect', 'yes');
                            } else {
                                $question->addChild('answerGiven', htmlspecialchars($answerManager->getAnswerText()));
                                $question->addChild('answerCorrect', 'no');
                            }
                        }
                    }
                }
            }

            $path = '/www/cdcmastery.com/xml-archive/' . $testManager->getUserUUID();
            $fileName = strtotime($testManager->getTestTimeCompleted()) . '#' . $testUUID . '.xml';
            $fileString = $path . '/' . $fileName;

            if (!file_exists($path)) {
                if (!mkdir($path, 0770, true)) {
                    $errorArray[] = "Could not create directory.";
                }
            }

            $dom = new DOMDocument('1.0');
            $dom->preserveWhiteSpace = false;
            $dom->formatOutput = true;
            $dom->loadXML($xml->asXML());
            $domXMLData = $dom->saveXML();

            if (!file_put_contents($fileString, $domXMLData)) {
                $errorArray[] = "Could not save " . $fileString;
            } else {
                $testManager->setTestArchived(true);

                if (!$testManager->saveTest(false)) {
                    $errorArray[] = "Could not update archive status for test " . $testUUID;
                    $log->setAction("ERROR_TEST_ARCHIVE");
                    $log->setDetail("Test UUID", $testUUID);
                    $log->setDetail("User UUID", $testManager->getUserUUID());
                    $log->setDetail("Error", $testManager->error);
                    $log->saveEntry();
                } else {
                    if (!$testManager->deleteTestData($testUUID, false)) {
                        $errorArray[] = "Could not delete test data for test " . $testUUID;
                        $log->setAction("ERROR_TEST_ARCHIVE");
                        $log->setDetail("Test UUID", $testUUID);
                        $log->setDetail("User UUID", $testManager->getUserUUID());
                        $log->setDetail("Error", "Could not delete test data.");
                        $log->saveEntry();
                    } else {
                        $log->setAction("TEST_ARCHIVE");
                        $log->setDetail("Test UUID", $testUUID);
                        $log->setDetail("User UUID", $testManager->getUserUUID());
                        $log->saveEntry();
                    }
                }
            }
        }

        $percentDone = intval(($testCount / $totalTests) * 100);

        echo "\033[5D";      // Move 5 characters backward
        echo str_pad($percentDone, 3, ' ', STR_PAD_LEFT) . " %";    // Output is always 5 characters long

        $testCount++;
    }
    echo "\033[5D";
    echo str_pad(100, 3, ' ', STR_PAD_LEFT) . " %";
    echo "\n";

    if (isset($errorArray)) {
        print_r($errorArray);
    }
}
else{
    echo "There are no tests available to archive.\n";
}