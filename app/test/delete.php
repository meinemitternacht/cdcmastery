<?php
/*
 * Get route variables
 */
$testType = isset($_SESSION['vars'][0]) ? strtolower($_SESSION['vars'][0]) : false;
$target	  = isset($_SESSION['vars'][1]) ? strtolower($_SESSION['vars'][1]) : false;

$testManager = new CDCMastery\TestManager($db, $systemLog, $afscManager);

if(!$testType){
    $systemMessages->addMessage("You must specify a type of test to delete.", "warning");
	$cdcMastery->redirect("/errors/500");
}
elseif(!$target){
    $systemMessages->addMessage("You must either specify a test to delete or delete all tests.", "warning");
	$cdcMastery->redirect("/errors/500");
}
else{
	if($testType == "incomplete"){
		if($target == "all"){
			if(isset($_POST['confirmIncompleteTestDeleteAll'])){
				if($testManager->deleteIncompleteTest(true,false,$_SESSION['userUUID'])){
					$userStatistics->deleteUserStatsCacheVal("getIncompleteTests");
                    $systemMessages->addMessage("Incomplete tests deleted successfully.", "success");
					$cdcMastery->redirect("/");
				}
				else{
					$userStatistics->deleteUserStatsCacheVal("getIncompleteTests");
					$systemMessages->addMessage("We could not delete your incomplete tests, please contact the support helpdesk.", "danger");
					$cdcMastery->redirect("/errors/500");
				}
			}
			else{ ?>
			<div class="container">
				<div class="row">
					<div class="4u">
						<section>
							<div class="sub-menu">
								<ul>
									<li><a href="/"><i class="icon-inline icon-20 ic-arrow-left"></i>Return to Home Page</a></li>
								</ul>
							</div>
						</section>
					</div>
				</div>
				<div class="row">
					<div class="8u">
						<section>
							<header>
								<h2>Confirm Delete All Incomplete Tests</h2>
							</header>
							<br>
							<form action="/test/delete/incomplete/all" method="POST">
								<input type="hidden" name="confirmIncompleteTestDeleteAll" value="1">
								If you wish to delete all incomplete tests you have started, please press continue.
								Otherwise, <a href="/">return to the home page</a>.
								<div class="clearfix">&nbsp;</div>
								<input type="submit" value="Continue">
							</form>
						</section>
					</div>
				</div>
			</div>
			<?php
			}
		}
		elseif($testManager->loadIncompleteTest($target)){
			if($testManager->getIncompleteUserUUID() != $_SESSION['userUUID']){
				$systemLog->setAction("ERROR_INCOMPLETE_TEST_DELETE");
				$systemLog->setDetail("Error", "User UUID does not match Test User UUID");
				$systemLog->setDetail("Test UUID", $target);
				$systemLog->setDetail("Test User UUID", $testManager->getIncompleteUserUUID());
				$systemLog->saveEntry();
				
				$systemMessages->addMessage("You cannot delete a test taken by another user.", "danger");
				$cdcMastery->redirect("/errors/403");
			}
			else{
				if(isset($_POST['confirmIncompleteTestDelete'])){
					if($testManager->deleteIncompleteTest(false,$target)){
						$userStatistics->deleteUserStatsCacheVal("getIncompleteTests");
                        $systemMessages->addMessage("Test deleted successfully.", "success");
						$cdcMastery->redirect("/");
					}
					else{
                        $systemMessages->addMessage("We could not delete that test, please contact the support helpdesk.", "danger");
						$cdcMastery->redirect("/errors/500");
					}
				}
				else{ ?>
				<div class="container">
					<div class="row">
						<div class="4u">
							<section>
								<div class="sub-menu">
									<ul>
										<li><a href="/"><i class="icon-inline icon-20 ic-arrow-left"></i>Return to Home Page</a></li>
									</ul>
								</div>
							</section>
						</div>
					</div>
					<div class="row">
						<div class="8u">
							<section>
								<header>
									<h2>Confirm Delete Incomplete Test</h2>
								</header>
								<br>
								<form action="/test/delete/incomplete/<?php echo $target; ?>" method="POST">
									<input type="hidden" name="confirmIncompleteTestDelete" value="1">
									If you wish to delete the incomplete test started on
                                    <strong><?php echo $cdcMastery->outputDateTime($testManager->getIncompleteTimeStarted(),$_SESSION['timeZone']); ?></strong>
									that is <strong><?php echo $testManager->getIncompletePercentComplete(); ?></strong> complete, please press continue.
									Otherwise, <a href="/">return to the home page</a>.
									<div class="clearfix">&nbsp;</div>
									<input type="submit" value="Continue">
								</form>
							</section>
						</div>
					</div>
				</div>
				<?php
				}
			}
		}
		else{
            $systemMessages->addMessage("The specified test does not exist.", "warning");
			$cdcMastery->redirect("/errors/500");
		}
	}
	else{
        $systemMessages->addMessage("You specified an invalid test type.", "warning");
		$cdcMastery->redirect("/errors/500");
	}
}