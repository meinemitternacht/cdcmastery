<?php
if(isset($_SESSION['vars'][0])){
	$testUUID = $_SESSION['vars'][0];
	
	$testManager = new testManager($db, $log, $afsc);
	
	if(!$testManager->resumeTest($testUUID)){
		$log->setAction("ERROR_TEST_RESUME");
		$log->setDetail("TEST UUID",$testUUID);
		$log->setDetail("SCRIPT LOCATION","test/resume -- testManager->resumeTest(testUUID)");
		$log->setDetail("MYSQL_ERROR",$testManager->error);
		$log->saveEntry();
	}
	else{
		if($testManager->getIncompleteUserUUID() != $_SESSION['userUUID']){
			$log->setAction("ACCESS_DENIED");
			$log->setDetail("TEST UUID",$testUUID);
			$log->setDetail("Error Detail","User attempted to resume a test that was not theirs.");
			$log->saveEntry();

            $sysMsg->addMessage("Sorry, you cannot resume another user's test.");
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