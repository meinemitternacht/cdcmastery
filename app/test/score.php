<?php
/**
 * Created by PhpStorm.
 * User: claude
 * Date: 10/26/14
 * Time: 12:32 AM
 */

$testUUID = isset($_SESSION['vars'][0]) ? $_SESSION['vars'][0] : false;
$testManager = new testManager($db, $log, $afsc);
$answerManager = new answerManager($db, $log);

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

                if(!$answerManager->getAnswerCorrect()){
                    $incorrectTotal++;
                }
                else{
                    $correctTotal++;
                }
            }

            $testScore = intval(($correctTotal / $testManager->getIncompleteTotalQuestions()) * 100);

            $testManager->setUUID($testUUID);
            $testManager->setUserUUID($testManager->getIncompleteUserUUID());
            $testManager->setAFSCList($testManager->getIncompleteAFSCList());
            $testManager->setTotalQuestions($testManager->getIncompleteTotalQuestions());
            $testManager->setQuestionsMissed($incorrectTotal);
            $testManager->setTestScore($testScore);
            $testManager->setTestTimeStarted($testManager->getIncompleteTimeStarted());
            $testManager->setTestTimeCompleted(date("Y-m-d H:i:s",time()));

            if($testManager->saveTest()){
                if($testManager->deleteIncompleteTest(false,$testUUID,false,false)){
                    $log->setAction("SCORE_TEST");
                    $log->setDetail("Test UUID",$testUUID);
                    $log->setDetail("Score",$testScore);
                    $log->setDetail("User UUID",$testManager->getIncompleteUserUUID());
                    $log->saveEntry();

                    if($testScore >= $cdcMastery->getPassingScore()){
                        $_SESSION['messages'][] = "Congratulations!  You passed the test.";
                        $cdcMastery->redirect("/test/view/".$testUUID);
                    }
                    else{
                        $_SESSION['messages'][] = "Sorry, you didn't pass the test.  Keep studying!";
                        $cdcMastery->redirect("/test/view/".$testUUID);
                    }
                }
                else{
                    $log->setAction("ERROR_SCORE_TEST");
                    $log->setDetail("Test UUID",$testUUID);
                    $log->setDetail("Score",$testScore);
                    $log->setDetail("User UUID",$testManager->getIncompleteUserUUID());
                    $log->setDetail("MySQL Error",$testManager->db->error);
                    $log->setDetail("Calling Script","/test/score");
                    $log->setDetail("Function","testManager->deleteIncompleteTest()");
                    $log->saveEntry();
                }
            }
            else{
                $log->setAction("ERROR_SCORE_TEST");
                $log->setDetail("Test UUID",$testUUID);
                $log->setDetail("Score",$testScore);
                $log->setDetail("User UUID",$testManager->getIncompleteUserUUID());
                $log->setDetail("MySQL Error",$testManager->db->error);
                $log->setDetail("Calling Script","/test/score");
                $log->setDetail("Function","testManager->saveTest()");
                $log->saveEntry();
            }
        }
        else{
            $_SESSION['messages'][] = "There is no test data for that test UUID.";
            $cdcMastery->redirect("/errors/500");
        }


        /*
         * Place a message in the session to let the user know if they passed or failed the test.vcfcv
         */
    }
    else{
        $_SESSION['messages'][]  = "That test does not exist.";
        $cdcMastery->redirect("/errors/404");
    }
}
else{
    $_SESSION['messages'][] = "You must provide a test UUID.";
    $cdcMastery->redirect("/errors/500");
}