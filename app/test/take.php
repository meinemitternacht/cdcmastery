<?php
if(isset($_SESSION['vars'][0])):
	/*
	 * Entry point for test in progress, or after resuming a test
	 */
	$testUUID = $_SESSION['vars'][0];
	
	/*
	 * Check if test is complete
	 */
else:
	/*
	 * Entry point for a new test
	 */
	if(!empty($_POST)){
		$testManager = new testManager($db, $log, $afsc);
		$testManager->newTest();
		
		foreach($_POST['userAFSCList'] as $afscUUID){
			$testManager->addAFSC($afscUUID);
		}
		
		if($testManager->populateQuestions()){
			$testManager->saveIncompleteTest();
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