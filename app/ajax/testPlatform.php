<?php
/*
 * AJAX entry point for the testing platform.
 * URL Structure:  /ajax/testPlatform/<test_uuid>/
 * Data Structure: /ajax/testPlatform/$_SESSION['vars'][0]
 * 
 * Data is requested and sent through POST method.
 */
$testManager = new testManager($db, $log, $afsc);

if(isset($_SESSION['vars'][0]))
	$testUUID = $_SESSION['vars'][0];

if(isset($_POST['action']))
	$userAction = $_POST['action'];

if(isset($_POST['actionData']))
	$actionData = $_POST['actionData'];

if(isset($testUUID)){
	if($testManager->loadIncompleteTest($testUUID)){
		if(isset($userAction)){
			switch($userAction){
				case "answerQuestion":
					if(isset($actionData)){
						$testManager->answerQuestion($testManager->incompleteQuestionList[($testManager->incompleteCurrentQuestion - 1)],$actionData);
						$testManager->navigateNextQuestion();
					}
				break;
				case "firstQuestion":
					$testManager->navigateFirstQuestion();
				break;
				case "previousQuestion":
					$testManager->navigatePreviousQuestion();
				break;
				case "nextQuestion":
					$testManager->navigateNextQuestion();
				break;
				case "lastQuestion":
					$testManager->navigateLastQuestion();
				break;
				case "specificQuestion":
					if(isset($actionData)){
						$testManager->navigateSpecificQuestion($actionData);
					}
				break;
				default:
					$log->setAction("AJAX_ACTION_ERROR");
					$log->setDetail("CALLING SCRIPT","/ajax/testPlatform");
					$log->setDetail("TEST UUID",$testUUID);
					$log->setDetail("USER ACTION",$userAction);
					$log->setDetail("ACTION DATA",$actionData);
					$log->saveEntry();
				break;
			}
		}
		
		if($testManager->incompleteQuestionsAnswered == $testManager->incompleteTotalQuestions){
			/*
			 * Test completed, go to scoring page
			 */

            echo $testManager->outputQuestionData($testManager->incompleteQuestionList[($testManager->incompleteCurrentQuestion - 1)],true);
        }
		else {
            /*
             * Show current question
             */
            echo $testManager->outputQuestionData($testManager->incompleteQuestionList[($testManager->incompleteCurrentQuestion - 1)]);
        }
	}
	else{
		echo "That test does not exist.";
	}
}
else{
	$log->setAction("AJAX_DIRECT_ACCESS");
	$log->setDetail("CALLING SCRIPT","/ajax/testPlatform");
	$log->saveEntry();

	$sysMsg->addMessage("Direct access to that script is not authorized.");
	$cdcMastery->redirect("/errors/403");
}