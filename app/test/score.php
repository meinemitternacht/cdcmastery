<?php
/**
 * Created by PhpStorm.
 * User: claude
 * Date: 10/26/14
 * Time: 12:32 AM
 */

$testUUID = isset($_SESSION['vars'][0]) ? $_SESSION['vars'][0] : false;
$testManager = new TestManager($db, $systemLog, $afscManager);
$answerManager = new AnswerManager($db, $systemLog);

$incorrectTotal = 0;
$correctTotal = 0;

if($testUUID){
    if($testManager->loadIncompleteTest($testUUID)){
        /*
         * Calculate the test score
         */
        $testData = $testManager->getTestData();

        if($testData){
            foreach($testData as $questionUUID => $answerUUID){
                $answerManager->loadAnswer($answerUUID);

                if($answerManager->getAnswerCorrect() == true){
                    $correctTotal++;
                }
                else{
                    $incorrectTotal++;
                }
            }

            $testScore = round(($correctTotal / $testManager->getIncompleteTotalQuestions()) * 100);

            $testManager->setUUID($testUUID);
            $testManager->setUserUUID($testManager->getIncompleteUserUUID());
            $testManager->setAFSCList($testManager->getIncompleteAFSCList());
            $testManager->setTotalQuestions($testManager->getIncompleteTotalQuestions());
            $testManager->setQuestionsMissed($incorrectTotal);
            $testManager->setTestScore($testScore);
            $testManager->setTestTimeStarted($testManager->getIncompleteTimeStarted());
            $testManager->setTestTimeCompleted(date("Y-m-d H:i:s",time()));

            if($testManager->saveTest(false)){
                if($testManager->deleteIncompleteTest(false,$testUUID,false,false,false)){
                    $systemLog->setAction("SCORE_TEST");
                    $systemLog->setDetail("Test UUID", $testUUID);
                    $systemLog->setDetail("Score", $testScore);
                    $systemLog->setDetail("User UUID", $testManager->getIncompleteUserUUID());
                    $systemLog->saveEntry();

                    if($testScore >= $cdcMastery->getPassingScore()){
                        $systemMessages->addMessage("Congratulations!  You passed the test.", "success");
                    }
                    else{
                        $systemMessages->addMessage("Sorry, you didn't pass the test.  Keep studying!", "danger");
                    }

                    $cdcMastery->redirect("/test/view/".$testUUID);
                }
                else{
                    $systemLog->setAction("ERROR_SCORE_TEST");
                    $systemLog->setDetail("Test UUID", $testUUID);
                    $systemLog->setDetail("Score", $testScore);
                    $systemLog->setDetail("User UUID", $testManager->getIncompleteUserUUID());
                    $systemLog->setDetail("MySQL Error", $testManager->db->error);
                    $systemLog->setDetail("Calling Script", "/test/score");
                    $systemLog->setDetail("Function", "testManager->deleteIncompleteTest()");
                    $systemLog->saveEntry();
                }
            }
            else{
                $systemLog->setAction("ERROR_SCORE_TEST");
                $systemLog->setDetail("Test UUID", $testUUID);
                $systemLog->setDetail("Score", $testScore);
                $systemLog->setDetail("User UUID", $testManager->getIncompleteUserUUID());
                $systemLog->setDetail("MySQL Error", $testManager->db->error);
                $systemLog->setDetail("Calling Script", "/test/score");
                $systemLog->setDetail("Function", "testManager->saveTest()");
                $systemLog->saveEntry();
            }
        }
        else{
            $systemMessages->addMessage("There is no test data for that test UUID.", "warning");
            $cdcMastery->redirect("/errors/500");
        }
    }
    else{
        $systemMessages->addMessage("That test does not exist.", "warning");
        $cdcMastery->redirect("/errors/404");
    }
}
else{
    $systemMessages->addMessage("You must provide a test UUID.", "warning");
    $cdcMastery->redirect("/errors/500");
}