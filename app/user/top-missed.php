<?php
$answerManager = new AnswerManager($db, $systemLog);
$questionManager = new QuestionManager($db, $systemLog, $afscManager, $answerManager);

$userStatistics->setUserUUID($_SESSION['userUUID']);
$topMissedQuestionArray = $userStatistics->getQuestionsMissedAcrossTests();

if(!empty($topMissedQuestionArray)): ?>
<!--[if !IE]><!-->
<style type="text/css">
/*
Max width before this PARTICULAR table gets nasty
This query will take effect for any screen smaller than 760px
and also iPads specifically.
*/
@media
only screen and (max-width: 760px),
(min-device-width: 768px) and (max-device-width: 1024px)  {

	/* Force table to not be like tables anymore */
	table, thead, tbody, th, td, tr {
		display: block;
	}

	/* Hide table headers (but not display: none;, for accessibility) */
	thead tr {
		position: absolute;
		top: -9999px;
		left: -9999px;
	}

	tr { border: 1px solid #ccc; }

	td {
		/* Behave  like a "row" */
		border: none;
		border-bottom: 1px solid #eee;
		position: relative;
		padding-left: 25%;
	}

	td:before {
		/* Now like a table header */
		position: absolute;
		/* Top/left values mimic padding */
		top: 6px;
		left: 6px;
		width: 20%;
		padding-right: 10px;
		white-space: nowrap;
	}

	/*
	Label the data
	*/
	table#missed-table-1 td:nth-of-type(1):before { content: "Question"; }
	table#missed-table-1 td:nth-of-type(2):before { content: "Times Missed"; }
}

/* Smartphones (portrait and landscape) ----------- */
@media only screen
and (min-device-width : 320px)
and (max-device-width : 480px) {
	body {
		padding: 0;
		margin: 0;
		width: 320px; }
	}

/* iPads (portrait and landscape) ----------- */
@media only screen and (min-device-width: 768px) and (max-device-width: 1024px) {
	body {
		width: 495px;
	}
}

</style>
<!--<![endif]-->
<div class="container">
	<div class="row">
		<div class="12u">
			<section>
				<header>
					<h2>Top 20 Missed Questions For <?php echo $userManager->getUserNameByUUID($_SESSION['userUUID']); ?></h2>
				</header>
				<a href="/user/profile">&laquo; Return to Profile</a>
				<br>
				<table id="missed-table-1">
					<tr>
						<th>Question</th>
						<th>AFSC</th>
						<th>Times Missed</th>
					</tr>
					<?php
					foreach($topMissedQuestionArray as $questionUUID => $timesMissed):
						$questionManager->loadQuestion($questionUUID);

						if($questionManager->queryQuestionFOUO($questionUUID)){
							$answerManager->setFOUO(true);
						}
						else{
							$answerManager->setFOUO(false);
						}

						$answerManager->loadAnswer($answerManager->getCorrectAnswer($questionUUID));
						?>
					<tr>
						<td style="padding:1em;">
							<strong>Q:</strong> &nbsp;<?php echo $questionManager->getQuestionText();  ?><br>
							<strong>A:</strong> &nbsp;<?php echo $answerManager->getAnswerText(); ?>
						</td>
						<td><?php echo $afscManager->getAFSCName($questionManager->getAFSCUUID()); ?></td>
						<td><?php echo $timesMissed; ?></td>
					</tr>
					<?php endforeach; ?>
				</table>
			</section>
		</div>
	</div>
</div>
<?php
else:
	$systemMessages->addMessage("There is not enough data in the database to build the Top Missed Questions report.  Check back after you have completed more tests.", "info");
	$cdcMastery->redirect("/user/profile");
endif;