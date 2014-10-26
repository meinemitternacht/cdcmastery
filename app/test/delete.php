<?php
/*
 * Get route variables
 */
$testType = isset($_SESSION['vars'][0]) ? strtolower($_SESSION['vars'][0]) : false;
$target	  = isset($_SESSION['vars'][1]) ? strtolower($_SESSION['vars'][1]) : false;

$testManager = new testManager($db, $log, $afsc);

if(!$testType){
	$_SESSION['error'][] = "You must specify a type of test to delete.";
	$cdcMastery->redirect("/errors/500");
}
elseif(!$target){
	$_SESSION['error'][] = "You must either specify a test to delete or delete all tests.";
	$cdcMastery->redirect("/errors/500");
}
else{
	if($testType == "incomplete"){
		if($target == "all"){
			if(isset($_POST['confirmIncompleteTestDeleteAll'])){
				if($testManager->deleteIncompleteTest(true,false,$_SESSION['userUUID'])){
					$_SESSION['messages'][] = "Incomplete tests deleted successfully.";
					$cdcMastery->redirect("/");
				}
				else{
					$_SESSION['error'][] = "We could not delete your incomplete tests, please <a href=\"http://helpdesk.cdcmastery.com\">submit a ticket</a>.";
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
									<li><a href="/"><i class="fa fa-caret-square-o-left fa-fw"></i>Return to Home Page</a></li>
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
								<br>
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
				$log->setAction("ERROR_INCOMPLETE_TEST_DELETE");
				$log->setDetail("Error","User UUID does not match Test User UUID");
				$log->setDetail("Test UUID",$target);
				$log->setDetail("Test User UUID",$testManager->getIncompleteUserUUID());
				$log->saveEntry();
				
				$_SESSION['error'][] = "You cannot delete a test taken by another user.";
				$cdcMastery->redirect("/errors/403");
			}
			else{
				if(isset($_POST['confirmIncompleteTestDelete'])){
					if($testManager->deleteIncompleteTest(false,$target)){
						$_SESSION['messages'][] = "Test deleted successfully.";
						$cdcMastery->redirect("/");
					}
					else{
						$_SESSION['error'][] = "We could not delete that test, please <a href=\"http://helpdesk.cdcmastery.com\">submit a ticket</a>.";
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
										<li><a href="/"><i class="fa fa-caret-square-o-left fa-fw"></i>Return to Home Page</a></li>
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
									If you wish to delete the incomplete test started on <?php echo $cdcMastery->outputDateTime($testManager->getIncompleteTimeStarted(),$_SESSION['timeZone']); ?>
									that is <?php echo $testManager->getIncompletePercentComplete(); ?> complete, please press continue.
									Otherwise, <a href="/">return to the home page</a>.
									<br>
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
			$_SESSION['error'][] = "The specified test does not exist.";
			$cdcMastery->redirect("/errors/500");
		}
	}
	else{
		$_SESSION['error'][] = "You specified an invalid test type.";
		$cdcMastery->redirect("/errors/500");
	}
}