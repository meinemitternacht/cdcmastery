<?php
if(isset($_SESSION['vars'][0])){
	$testUUID = $_SESSION['vars'][0];
	
	$testManager = new TestManager($db, $systemLog, $afscManager);
	
	if(!$testManager->resumeTest($testUUID)){
		$systemLog->setAction("ERROR_TEST_RESUME");
		$systemLog->setDetail("TEST UUID", $testUUID);
		$systemLog->setDetail("SCRIPT LOCATION", "test/resume -- testManager->resumeTest(testUUID)");
		$systemLog->setDetail("MYSQL_ERROR", $testManager->error);
		$systemLog->saveEntry();
	}
	else{
		if($testManager->getIncompleteUserUUID() != $_SESSION['userUUID']){
			$systemLog->setAction("ACCESS_DENIED");
			$systemLog->setDetail("TEST UUID", $testUUID);
			$systemLog->setDetail("Error Detail", "User attempted to resume a test that was not theirs.");
			$systemLog->saveEntry();

            $systemMessages->addMessage("Sorry, you cannot resume another user's test.", "danger");
			$cdcMastery->redirect("/errors/403");
		}
		else{
			$_SESSION['testUUID'] = $testUUID;
			$cdcMastery->redirect("/test/take/".$testUUID);
		}
	}
}
else{
	$cdcMastery->redirect("/test/take");
}