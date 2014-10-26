<?php
$testUUID = isset($_SESSION['vars'][0]) ? $_SESSION['vars'][0] : false;

$testManager = new testManager($db, $log, $afsc);
$answerManager = new answerManager($db, $log);
$questionManager = new questionManager($db, $log, $afsc, $answerManager);
$user->loadUser($_SESSION['userUUID']);

/*
 * Check if test UUID is empty
 */
if(!$testUUID){
	$_SESSION['errors'][] = "You must provide a test to view.";
	$cdcMastery->redirect("/errors/404");
}

/*
 * Check test validity
 */
if(!$testManager->loadTest($testUUID)){
	$_SESSION['errors'][] = "That test does not exist.";
	$cdcMastery->redirect("/errors/404");
}

/*
 * Check test ownership, if role is user.  Deny access to tests that are not their own.
 */
if($roles->getRoleType($user->getUserRole()) == "user" && $testManager->getUserUUID() != $_SESSION['userUUID']){
	$_SESSION['errors'][] = "You are not authorized to view that test.";
	$cdcMastery->redirect("/errors/403");
}

$rawAFSCList = $testManager->getAFSCList();

foreach($rawAFSCList as $key => $val){
	$rawAFSCList[$key] = $afsc->getAFSCName($val);
}

if(count($rawAFSCList) > 1){
	$testAFSCList = implode(",",$rawAFSCList);
}
else{
	$testAFSCList = $rawAFSCList[0];
}
?>
<div class="container">
	<div class="row">
		<div class="4u">
			<section>
				<header>
					<h2>Test Details</h2>
				</header>
				<table>
					<?php if($testManager->getUserUUID() != $_SESSION['userUUID']): ?>
					<tr>
						<th>Test Owner</th>
						<td><a href="/admin/users/<?php echo $testManager->getUserUUID(); ?>"><?php echo $user->getUserNameByUUID($testManager->getUserUUID()); ?></a></td>
					</tr>
					<?php endif; ?>
					<tr>
						<th>Time Started</th>
						<td><?php echo $cdcMastery->outputDateTime($testManager->getTestTimeStarted(), $_SESSION['timeZone']); ?></td>
					</tr>
					<tr>
						<th>Time Completed</th>
						<td><?php echo $cdcMastery->outputDateTime($testManager->getTestTimeCompleted(), $_SESSION['timeZone']); ?></td>
					</tr>
					<tr>
						<th>AFSC</th>
						<td><?php echo $testAFSCList; ?></td>
					</tr>
					<tr>
						<th>Questions</th>
						<td><?php echo $testManager->getTotalQuestions(); ?></td>
					</tr>
					<tr>
						<th>Score</th>
						<td><?php echo $testManager->getTestScore(); ?>%</td>
					</tr>
				</table>
			</section>
		</div>
		<div class="8u">
			<section>
				<header>
					<h2>Test Results</h2>
				</header>
				<?php 
				$testManager->loadTestData($testUUID);
				$testData = $testManager->getTestData();
				
				$i=1;
				foreach($testData as $questionUUID => $answerUUID):
					$questionManager->loadQuestion($questionUUID);
					$answerManager->setFOUO($questionManager->queryQuestionFOUO($questionUUID));
					$answerManager->loadAnswer($answerUUID);
					?>
					<ul>
						<li style="padding:0.3em;font-size:1.1em;">
							<strong><?php echo $i; ?>. <?php echo $questionManager->getQuestionText(); ?></strong>
						</li>
						<li style="padding:0.3em">
							<?php if($answerManager->getAnswerCorrect()): ?>
							<i class="text-success fa fa-check-circle fa-lg fa-fw"></i>
							<?php else: ?>
							<i class="text-warning fa fa-times-circle fa-lg fa-fw"></i>
							<?php endif; ?>
							<?php echo $answerManager->getAnswerText(); ?>
						</li>
					</ul>
					<?php $i++; ?>
				<?php endforeach; ?>
			</section>
		</div>
	</div>
</div>