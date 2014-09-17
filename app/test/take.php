<?php
$testManager = new testManager($db, $log, $afsc);

if(isset($_SESSION['vars'][0])):
	/*
	 * Entry point for test in progress, or after resuming a test
	 */
	$testUUID = $_SESSION['vars'][0];
	
	/*
	 * Ensure test is valid
	 */
	if($testManager->loadIncompleteTest($testUUID)){ ?>
		<script type="text/javascript">
	
		$(document).ready(function() {
			function loading_show(){
				$('#loading').html("<img src='/images/loader.gif'>").fadeIn('fast');
			}
			
			function loading_hide(){
				$('#loading').fadeOut('fast');
			}
			
			function submitAnswer(answer){
				loading_show();
				
				$.ajax({
					type: "POST",
					url: "/ajax/testPlatform/<?php echo $testUUID; ?>",
					data: { 'action':'answerQuestion','actionData':answer },
					success: function(response){
						setTimeout("finishAjax('questionAnswer', '" + escape(response) + "')", 500);
					}
				});
			}
			
			
			$.post("/ajax/testPlatform/<?php echo $testUUID; ?>", {
				action: 'specificQuestion',
				actionData: '1'
				}, function(response) {
					setTimeout("finishAjax('questionAnswer', '" + escape(response) + "')", 500);
			});
			
			$(document).on('click','#answer1',function(){
				var ansID = $(this).attr('p');
				submitAnswer(ansID);
			});
			
			$(document).on('click','#answer2',function(){
				var ansID = $(this).attr('p');
				submitAnswer(ansID);
			});

			$(document).on('click','#answer3',function(){
				var ansID = $(this).attr('p');
				submitAnswer(ansID);
			});

			$(document).on('click','#answer4',function(){
				var ansID = $(this).attr('p');
				submitAnswer(ansID);
			});
			
			$('#goFirst').click(function() {
				loading_show();
				
				$.post("/ajax/testPlatform/<?php echo $testUUID; ?>", {
					action: 'firstQuestion'
				}, function(response) {
					setTimeout("finishAjax('questionAnswer', '" + escape(response) + "')", 500);
				});
				
				return false;
			
			});
			
			$('#goPrevious').click(function() {
				loading_show();
				
				$.post("/ajax/testPlatform/<?php echo $testUUID; ?>", {
					action: 'previousQuestion'
				}, function(response) {
					setTimeout("finishAjax('questionAnswer', '" + escape(response) + "')", 500);
				});
				
				return false;
			
			});
			
			$('#goNext').click(function() {
				loading_show();
				
				$.post("/ajax/testPlatform/<?php echo $testUUID; ?>", {
					action: 'nextQuestion'
				}, function(response) {
					setTimeout("finishAjax('questionAnswer', '" + escape(response) + "')", 500);
				});
				
				return false;
			
			});
			
			$('#goLast').click(function() {
				loading_show();
				
				$.post("/ajax/testPlatform/<?php echo $testUUID; ?>", {
					action: 'lastQuestion'
				}, function(response) {
					setTimeout("finishAjax('questionAnswer', '" + escape(response) + "')", 500);
				});
				
				return false;
			
			});
			
			});
			
			function finishAjax(id, response)
			{	
				$('#loading').fadeOut('fast');
				$('#' + id).html(unescape(response));
				$('.test-nav').fadeIn('fast');
				
				var submitTest = document.getElementById("submitTest");
				if (submitTest != null)
				{
					$('#storeAnswer').fadeOut();
				}
				else
				{
					$('#storeAnswer').show();
				}
			}
			
		</script>
		<div id="loading"><img src="/images/loader.gif" /></div>
		<div class="container">
			<div class="row">
				<div class="12u">
					<section>
						<div id="questionAnswer"></div>
					</section>
				</div>
			</div>
			<div class="row">
				<div class="12u">
					<section>
						<div class="test-nav" style="display: none;">
							<button class="test-nav-button" id="goFirst">&lt;&lt;</button>
							<button class="test-nav-button" id="goPrevious">&lt;</button>
							<button class="test-nav-button" id="goNext">&gt;</button>
							<button class="test-nav-button" id="goLast">&gt;&gt;</button>
						</div>
						<div class="clearfix"></div>
					</section>
				</div>
			</div>
		</div>
		<?
	}
	elseif($testManager->loadTest($testUUID)){
		/*
		 * Test has already been scored
		 */
		$cdcMastery->redirect("/test/view/".$testUUID);
	}
	else{
		/*
		 * Test does not exist.
		 */
		$_SESSION['error'][] = "Sorry, that test does not exist.";
		$cdcMastery->redirect("/errors/404");
	}
else:
	/*
	 * Entry point for a new test
	 */
	if(!empty($_POST)){
		$testManager->newTest();
		
		foreach($_POST['userAFSCList'] as $afscUUID){
			$testManager->addAFSC($afscUUID);
		}
		
		if($testManager->populateQuestions()){
			if($testManager->incompleteTotalQuestions > 1){
				$testManager->saveIncompleteTest();
				
				$log->setAction("STARTED_TEST");
				$log->setDetail("TEST UUID", $testManager->getIncompleteTestUUID());
				$log->setDetail("AFSC ARRAY", serialize($testManager->getIncompleteAFSCList()));
				$log->saveEntry();
				
				$cdcMastery->redirect("/test/take/".$testManager->getIncompleteTestUUID());
			}
			else{
				/*
				 * No questions in the database for this test.  Make it pretty!
				 */
				echo $testManager->error;
				echo "<br>";
				echo "This test does not have any questions.";
			}
		}
		else{
			echo $testManager->error;
		}
	}
	else{
		?>
		<div id="content" class="8u skel-cell-important">
			<section>
				<header>
					<h3>Take a test</h3>
				</header>
				<form action="/test/take" method="POST">
					<?php
					$afscList = $userStatistics->getAFSCAssociations();
					
					$i=0;
					foreach($afscList as $afscUUID): ?>
						<input type="checkbox" name="userAFSCList[]" id="checkbox<?php echo $i; ?>" value="<?php echo $afscUUID; ?>"><label for="checkbox<?php echo $i; ?>"><?php echo $afsc->getAFSCName($afscUUID); ?></label><br>
						<?php 
						$i++;
					endforeach; 
					?>
					<input type="submit" value="Start Test">
				</form>
			</section>
		</div>
		<?php
	}
endif;