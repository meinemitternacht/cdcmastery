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

$testManager = new TestManager($db, $systemLog, $afscManager);
$answerManager = new AnswerManager($db, $systemLog);
$questionManager = new QuestionManager($db, $systemLog, $afscManager, $answerManager);

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
                $rawAFSCList[$key] = $afscManager->getAFSCName($val);
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
                        $question->addChild('answerGiven', htmlspecialchars($answerManager->getAnswerText()));
                        if ($answerManager->getAnswerCorrect()) {
                            $question->addChild('answerCorrect', 'yes');
                        } else {
                            $question->addChild('answerCorrect', 'no');
                        }
                    } else {
                        $archivedText = $questionManager->getArchivedQuestionText($questionUUID);
                        if ($archivedText) {
                            $question = $xml->addChild('question');
                            $question->addChild('questionText', htmlspecialchars($archivedText));

                            $answerManager->loadArchivedAnswer($answerUUID);
                            $question->addChild('answerGiven', htmlspecialchars($answerManager->getAnswerText()));

                            if ($answerManager->getAnswerCorrect()) {
                                $question->addChild('answerCorrect', 'yes');
                            } else {
                                $question->addChild('answerCorrect', 'no');
                            }
                        }
                    }
                }
            }

            $path = $configurationManager->getXMLArchiveConfiguration('directory') . $testManager->getUserUUID();
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
                    $systemLog->setAction("ERROR_TEST_ARCHIVE");
                    $systemLog->setDetail("Test UUID", $testUUID);
                    $systemLog->setDetail("User UUID", $testManager->getUserUUID());
                    $systemLog->setDetail("Error", $testManager->error);
                    $systemLog->saveEntry();
                } else {
                    if (!$testManager->deleteTestData($testUUID, false)) {
                        $errorArray[] = "Could not delete test data for test " . $testUUID;
                        $systemLog->setAction("ERROR_TEST_ARCHIVE");
                        $systemLog->setDetail("Test UUID", $testUUID);
                        $systemLog->setDetail("User UUID", $testManager->getUserUUID());
                        $systemLog->setDetail("Error", "Could not delete test data.");
                        $systemLog->saveEntry();
                    } else {
                        $systemLog->setAction("TEST_ARCHIVE");
                        $systemLog->setDetail("Test UUID", $testUUID);
                        $systemLog->setDetail("User UUID", $testManager->getUserUUID());
                        $systemLog->saveEntry();
                    }
                }
            }
        }

        $percentDone = intval(($testCount / $totalTests) * 100);

        echo "\033[5D";
        echo str_pad($percentDone, 3, ' ', STR_PAD_LEFT) . " %";

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