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

/*
 * If user is a supervisor, check that this test is owned by a subordinate
 */

if($cdcMastery->verifySupervisor()){
	$supUser = new user($db,$log,$emailQueue);
	$supOverview = new supervisorOverview($db,$log,$userStatistics,$supUser,$roles);

	$supOverview->loadSupervisor($_SESSION['userUUID']);

	$subordinateUsers = $supOverview->getSubordinateUserList();

	if(empty($subordinateUsers)):
		$sysMsg->addMessage("You do not have any subordinate users.");
		$cdcMastery->redirect("/supervisor/associate");
	endif;

	if(!in_array($testManager->getUserUUID(),$subordinateUsers)){
		$sysMsg->addMessage("That user is not associated with your account.");
		$cdcMastery->redirect("/supervisor/overview");
	}
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
						<?php if($cdcMastery->verifySupervisor()): ?>
							<td><a href="/supervisor/profile/<?php echo $testManager->getUserUUID(); ?>"><?php echo $user->getUserNameByUUID($testManager->getUserUUID()); ?></a></td>
						<?php else: ?>
							<td><a href="/admin/users/<?php echo $testManager->getUserUUID(); ?>"><?php echo $user->getUserNameByUUID($testManager->getUserUUID()); ?></a></td>
						<?php endif; ?>
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
						<td><?php echo str_replace(",","<br>",$testAFSCList); ?></td>
					</tr>
					<tr>
						<th>Questions</th>
						<td><?php echo $testManager->getTotalQuestions(); ?></td>
					</tr>
					<tr>
						<th>Score</th>
						<td><?php echo $testManager->getTestScore(); ?>%</td>
					</tr>
                    <tr>
                        <th># Missed</th>
                        <td><?php echo $testManager->getQuestionsMissed(); ?></td>
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
                $c=0;
				foreach($testData as $questionUUID => $answerUUID):
					$questionManager->loadQuestion($questionUUID);
					$answerManager->setFOUO($questionManager->queryQuestionFOUO($questionUUID));
					$answerManager->loadAnswer($answerUUID);
					?>
					<ul style="border-left: 0.5em solid #aaa;background-color:<?php $color = ($c==0)?"#eee":"#ddd"; echo $color; ?>">
						<li style="padding:0.3em;font-size:1.1em;">
							<strong><?php echo $i; ?>. <?php echo $questionManager->getQuestionText(); ?></strong>
						</li>
						<li style="padding:0.3em">
							<?php if($answerManager->getAnswerCorrect()): ?>
							<span class="text-success">
							<?php else: ?>
							<span class="text-warning"><i class="icon-inline icon-20 ic-delete"></i>
							<?php endif; ?>
							    <?php echo $answerManager->getAnswerText(); ?>
                            </span>
						</li>
					</ul>
					<?php $i++; ?>
                    <?php $c=($c==0)?1:0; ?>
				<?php endforeach; ?>
			</section>
		</div>
	</div>
</div>