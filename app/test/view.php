<?php
$testUUID = isset($_SESSION['vars'][0]) ? $_SESSION['vars'][0] : false;
$showAnswers = isset($_SESSION['vars'][1]) ? ($_SESSION['vars'][1] == "show-all") ? true : false : false;

$testManager = new TestManager($db, $systemLog, $afscManager);
$answerManager = new AnswerManager($db, $systemLog);
$questionManager = new QuestionManager($db, $systemLog, $afscManager, $answerManager);
$userManager->loadUser($_SESSION['userUUID']);

/*
 * Check if test UUID is empty
 */
if(!$testUUID){
    $systemMessages->addMessage("You must select a test to view.", "warning");
	$cdcMastery->redirect("/errors/404");
}

/*
 * Check test validity
 */
if(!$testManager->loadTest($testUUID)){
    $systemMessages->addMessage("That test does not exist.", "warning");
	$cdcMastery->redirect("/errors/404");
}

/*
 * Check test ownership, if role is user.  Deny access to tests that are not their own.
 */
if($roleManager->getRoleType($userManager->getUserRole()) == "user" && $testManager->getUserUUID() != $_SESSION['userUUID']){
	$systemMessages->addMessage("You are not authorized to view that test.", "danger");
	$cdcMastery->redirect("/errors/403");
}

/*
 * If user is a supervisor, check that this test is owned by a subordinate
 */

if($cdcMastery->verifySupervisor() && $testManager->getUserUUID() != $_SESSION['userUUID']){
	$supUser = new UserManager($db, $systemLog, $emailQueue);
	$supOverview = new SupervisorOverview($db, $systemLog, $userStatistics, $supUser, $roleManager);

	$supOverview->loadSupervisor($_SESSION['userUUID']);

	$subordinateUsers = $supOverview->getSubordinateUserList();

	if(empty($subordinateUsers)):
		$systemMessages->addMessage("You do not have any subordinate users.", "info");
		$cdcMastery->redirect("/supervisor/subordinates");
	endif;

	if(!in_array($testManager->getUserUUID(),$subordinateUsers)){
		$systemMessages->addMessage("That user is not associated with your account.", "danger");
		$cdcMastery->redirect("/supervisor/overview");
	}
}

$rawAFSCList = $testManager->getAFSCList();

foreach($rawAFSCList as $key => $val){
    if($cdcMastery->verifyTrainingManager() || $cdcMastery->verifyAdmin()) {
        $rawAFSCList[$key] = "<a href=\"/admin/cdc-data/".$val."\" title=\"View CDC Data\">".$afscManager->getAFSCName($val)."</a>";
    }
    else{
        $rawAFSCList[$key] = $afscManager->getAFSCName($val);
    }
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
							<td><a href="/supervisor/profile/<?php echo $testManager->getUserUUID(); ?>"><?php echo $userManager->getUserNameByUUID($testManager->getUserUUID()); ?></a></td>
						<?php else: ?>
							<td><a href="/admin/users/<?php echo $testManager->getUserUUID(); ?>"><?php echo $userManager->getUserNameByUUID($testManager->getUserUUID()); ?></a></td>
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
                <div class="clearfix">&nbsp;</div>
                <?php if(!$showAnswers): ?>
                <a href="/test/view/<?php echo $testUUID; ?>/show-all" title="Toggle all answers">Show all answers</a>
                <?php else: ?>
                <a href="/test/view/<?php echo $testUUID; ?>" title="Toggle all answers">Hide extra answers</a>
                <?php endif; ?>
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

                if($testManager->getTestArchived()): ?>
                    <p>This test has been archived.  Please contact us using the "Support" link at the top of the page to request a copy.  In the future, you will be able to download a copy of your archived tests for personal use.</p>
                <?php
                elseif(!empty($testData) && is_array($testData)): ?>
                    <p>The results of your test are shown below.  By hovering over an answer, you can view how often you have selected that particular answer across all of your tests. Clicking the
                    "Show all answers" link below the test details will present all of the answers for each question.</p>
                    <?php
                    $i=1;
                    $c=0;
                    foreach($testData as $questionUUID => $answerUUID):
                        if($questionManager->loadQuestion($questionUUID)) {
                            $answerManager->setFOUO($questionManager->queryQuestionFOUO($questionUUID));

                            if(!$showAnswers) {
                                $answerManager->loadAnswer($answerUUID);
                                ?>
                                <ul style="border-left: 0.5em solid #aaa;background-color:<?php $color = ($c == 0) ? "#eee" : "#ddd";
                                echo $color; ?>">
                                    <li style="padding:0.3em;font-size:1.1em;">
                                        <strong><?php echo $i; ?>
                                            . <?php echo $questionManager->getQuestionText(); ?></strong>
                                    </li>
                                    <?php
                                    $questionOccurrences = $userStatistics->getQuestionOccurrences($testManager->getUserUUID(),$questionUUID);
                                    $answerOccurrences = $userStatistics->getAnswerOccurrences($testManager->getUserUUID(),$answerUUID);

                                    if(($questionOccurrences > 0) && ($answerOccurrences > 0)){
                                        $pickPercent = (($answerOccurrences)/($questionOccurrences) * 100);
                                        $pickPercentString = "You picked this answer " . round($pickPercent,2) . "% of the time. The answer was picked " . $answerOccurrences . " " . (($answerOccurrences == 1) ? "time" : "times") . " and the question has been seen " . $questionOccurrences . " " . (($questionOccurrences == 1) ? "time." : "times.");
                                    }
                                    else{
                                        $pickPercentString = "There is no data to get usage statistics for this question/answer combination.";
                                    }
                                    ?>
                                    <li style="padding:0.3em; cursor: pointer;" title="<?php echo $pickPercentString; ?>">
                                        <?php if ($answerManager->getAnswerCorrect()): ?>
                                        <span class="text-success">
                                    <?php else: ?>
                                            <span class="text-warning"><i class="icon-inline icon-20 ic-delete"></i>
                                                <?php endif; ?>
                                                <?php echo $answerManager->getAnswerText(); ?>
                                        </span>
                                            <?php if (!$answerManager->getAnswerCorrect()): ?>
                                                <?php if ($answerManager->loadAnswer($answerManager->getCorrectAnswer($questionUUID))): ?>
                                                    <br>
                                                    <strong>Correct Answer</strong>:<span
                                                        style="padding-left: 1em;"><em><?php echo $answerManager->getAnswerText(); ?></em></span>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                    </li>
                                </ul>
                                <?php
                            }
                            else{
                                /*
                                 * Show all answers for each question, along with how many times that answer has been used.
                                 */
                                $answerManager->setQuestionUUID($questionUUID);
                                $answerUUIDList = $answerManager->listAnswersByQuestion();
                                ?>
                                <ul style="border-left: 0.5em solid #aaa;background-color:<?php $color = ($c == 0) ? "#eee" : "#ddd";
                                echo $color; ?>">
                                    <li style="padding:0.3em;font-size:1.1em;">
                                        <strong><?php echo $i; ?>
                                            . <?php echo $questionManager->getQuestionText(); ?></strong>
                                    </li>
                                    <li style="padding:0.3em">
                                        <ul>
                                            <?php $questionOccurrences = $userStatistics->getQuestionOccurrences($testManager->getUserUUID(),$questionUUID); ?>
                                            <?php foreach($answerUUIDList as $allAnswersUUID => $allAnswersData): ?>
                                                <?php
                                                $answerOccurrences = $userStatistics->getAnswerOccurrences($testManager->getUserUUID(),$allAnswersUUID);

                                                if(!$questionOccurrences){
                                                    $pickPercentString = "This question has not been answered by you yet.";
                                                }
                                                elseif(!$answerOccurrences){
                                                    $pickPercentString = "You have never selected this answer.";
                                                }
                                                elseif(($questionOccurrences > 0) && ($answerOccurrences > 0)){
                                                    $pickPercent = (($answerOccurrences)/($questionOccurrences) * 100);
                                                    $pickPercentString = "You picked this answer " . round($pickPercent,2) . "% of the time. The answer was picked " . $answerOccurrences . " " . (($answerOccurrences == 1) ? "time" : "times") . " and the question has been seen " . $questionOccurrences . " " . (($questionOccurrences == 1) ? "time." : "times.");
                                                }
                                                else{
                                                    $pickPercentString = "There is no data to get usage statistics for this question/answer combination.";
                                                }
                                                ?>
                                                <li style="cursor: pointer;" title="<?php echo $pickPercentString; ?>">
                                                <?php $answerManager->loadAnswer($allAnswersUUID); ?>
                                                <?php if($allAnswersUUID == $answerUUID): ?>
                                                    <?php if($answerManager->getAnswerCorrect()): ?>
                                                    <span class="text-success"><?php echo $answerManager->getAnswerText(); ?></span> &laquo; <strong>Your Answer</strong>
                                                    <?php else: ?>
                                                    <span class="text-warning"><?php echo $answerManager->getAnswerText(); ?></span> &laquo; <strong>Your Answer</strong>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <?php if($answerManager->getAnswerCorrect()): ?>
                                                        <span class="text-success"><?php echo $answerManager->getAnswerText(); ?></span>
                                                    <?php else: ?>
                                                        <span class="text-warning"><?php echo $answerManager->getAnswerText(); ?></span>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </li>
                                </ul>
                                <?php
                            }
                        }
                        else{
                            $archivedText = $questionManager->getArchivedQuestionText($questionUUID);
                            if($archivedText):
                                $answerManager->loadArchivedAnswer($answerUUID); ?>
                                <ul style="border-left: 0.5em solid #aaa;background-color:<?php $color = ($c == 0) ? "#eee" : "#ddd";
                                echo $color; ?>">
                                    <li style="padding:0.3em;font-size:1.1em;">
                                        <strong><?php echo $i; ?>. <?php echo $archivedText; ?></strong>
                                    </li>
                                    <li style="padding:0.3em">
                                        <?php if ($answerManager->getAnswerCorrect()): ?>
                                        <span class="text-success">
                                <?php else: ?>
                                            <span class="text-warning"><i class="icon-inline icon-20 ic-delete"></i>
                                                <?php endif; ?>
                                                <?php echo $answerManager->getAnswerText(); ?>
                                </span>
                                    </li>
                                </ul>
                            <?php
                            endif;
                        }?>
                        <?php $i++; ?>
                        <?php $c = ($c == 0) ? 1 : 0; ?>
                    <?php endforeach; ?>
                <?php else: ?>
                This test was taken before the system kept records of question and answer data.
                <?php endif; ?>
			</section>
		</div>
	</div>
</div>