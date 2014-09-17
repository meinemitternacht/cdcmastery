<?php
if(isset($_SESSION['vars'][0])):
	$targetUUID = $_SESSION['vars'][0];
	$userProfile = new user($db, $log);
	$userProfileStatistics = new userStatistics($db, $log, $roles);
	if(!$userProfile->loadUser($targetUUID)){
		echo "That user does not exist.";
	}
	else{
		$userProfileStatistics->setUserUUID($targetUUID);
		?>
		<script>
		$(function() {
			$( "#history-tabs" ).tabs();
		});
		</script>
		<section>
			<header>
				<h2><?php echo $userProfile->getFullName(); ?></h2>
			</header>
			<a href="/admin/profile" class="button">&laquo; Back</a>
			<a href="/admin/user/<?php echo $targetUUID; ?>/edit" class="button">Edit</a>
			<a href="/admin/user/<?php echo $targetUUID; ?>/delete" class="button">Delete</a>
			<a href="/admin/user/<?php echo $targetUUID; ?>/reset-password" class="button">Reset Password</a>
			<a href="/admin/user/<?php echo $targetUUID; ?>/message" class="button">Message</a>
		</section>
		<div class="container">
			<div class="row">
				<div class="6u">
					<section>
						<div class="tablecloth maxWidth">
							<table cellspacing="0" cellpadding="0">
								<tr>
									<th colspan="2">User Information</th>
								</tr>
								<tr>
									<td><strong>Base</strong></td>
									<td><?php echo $bases->getBaseName($userProfile->getUserBase()); ?></td>
								</tr>
								<tr>
									<td><strong>Office Symbol</strong></td>
									<td><?php if($userProfile->getUserOfficeSymbol()){ echo $officeSymbol->getOfficeSymbol($userProfile->getUserOfficeSymbol()); } else { echo "N/A"; } ?></td>
								</tr>
								<tr>
									<td><strong>Date Registered</strong></td>
									<td><?php echo $userProfile->getUserDateRegistered(); ?></td>
								</tr>
								<tr>
									<td><strong>Last Login</strong></td>
									<td><?php echo $userProfile->getUserLastLogin(); ?></td>
								</tr>
								<tr>
									<td><strong>Role</strong></td>
									<td><?php echo $roles->getRoleName($userProfile->getUserRole()); ?></td>
								</tr>
								<tr>
									<th colspan="2">Personal Details</th>
								</tr>
								<tr>
									<td><strong>E-Mail</strong></td>
									<td><?php echo $userProfile->getUserEmail(); ?></td>
								</tr>
								<tr>
									<td><strong>Time Zone</strong></td>
									<td><?php echo $userProfile->getUserTimeZone(); ?></td>
								</tr>
								<tr>
									<td><strong>Username</strong></td>
									<td><?php echo $userProfile->getUserHandle(); ?></td>
								</tr>
								<tr>
									<th colspan="2"><div class="text-float-left">AFSC Associations</div><div class="text-float-right text-white"><a href="/admin/user/<?php echo $targetUUID; ?>/edit-afsc-associations">Edit &raquo;</a></div></th>
								</tr>
								<tr>
									<td><strong>Associated With</strong></td>
									<td>
										<?php
										$afscList = $userProfileStatistics->getAFSCAssociations();
										
										if(!$afscList){
											echo "No associations.";
										}
										else{
											foreach($afscList as $userAFSC){
												echo $afsc->getAFSCName($userAFSC)."<br />";
											}
										}
										?>
									</td>
								</tr>
								<tr>
									<td><strong>Pending Associations</strong></td>
									<td>
										<?php
										$afscList = $userProfileStatistics->getPendingAFSCAssociations();
										
										if(!$afscList){
											echo "No pending associations.";
										}
										else{
											foreach($afscList as $userAFSC){
												echo $afsc->getAFSCName($userAFSC)."<br />";
											}
										}
										?>
									</td>
								</tr>
							</table>
						</div>
					</section>
				</div>
				<div class="6u">
					<section>
						<div class="tablecloth">
							<table cellspacing="0" cellpadding="0">
								<tr>
									<th colspan="2">General Statistics</th>
								</tr>
								<tr>
									<td><strong>Log Entries</strong></td>
									<td><div class="text-float-left"><?php echo $userProfileStatistics->getLogEntries(); ?></div><div class="text-float-right"><a href="/admin/user/<?php echo $targetUUID; ?>/log">View &raquo;</a></div></td>
								</tr>
								<tr>
									<th colspan="2">Testing Statistics</th>
								</tr>
								<tr>
									<td><strong>Average Score</strong></td>
									<td><?php echo $userProfileStatistics->getAverageScore(); ?></td>
								</tr>
								<tr>
									<td><strong>Completed Tests</strong></td>
									<td><?php echo $userProfileStatistics->getCompletedTests(); ?></td>
								</tr>
								<tr>
									<td><strong>Incomplete Tests</strong></td>
									<td><?php echo $userProfileStatistics->getIncompleteTests(); ?></td>
								</tr>
								<tr>
									<td><strong>Total Tests</strong></td>
									<td><?php echo $userProfileStatistics->getTotalTests(); ?></td>
								</tr>
								<tr>
									<td><strong>Questions Answered</strong></td>
									<td><?php echo $userProfileStatistics->getQuestionsAnswered(); ?></td>
								</tr>
								<tr>
									<td><strong>Questions Missed</strong></td>
									<td><?php echo $userProfileStatistics->getQuestionsMissed(); ?></td>
								</tr>
								<tr>
									<th colspan="2"><div class="text-float-left">User Associations</div><div class="text-float-right text-white"><a href="/admin/user/<?php echo $targetUUID; ?>/edit-user-associations">Edit &raquo;</a></div></th>
								</tr>
								<?php
								$userRole = $roles->verifyUserRole($targetUUID);
								if($userRole == "supervisor"): ?>
								<tr>
									<td><strong>Supervisor For</strong></td>
									<td>
										<div class="associationList">
										<?php
										$supervisorAssociations = $userProfileStatistics->getSupervisorAssociations();
										
										if(!empty($supervisorAssociations) && is_array($supervisorAssociations)){
											$supervisorAssociations = $userProfile->resolveUserNames($supervisorAssociations);
											
											foreach($supervisorAssociations as $key => $subordinate){
												echo '<a href="/admin/profile/'.$key.'">'.$subordinate.'</a>';
												echo "<br />\n";
											}
										}
										else{
											echo "No associations in database.";
										}
										?>
										</div>
									</td>
								</tr>
								<?php elseif($userRole == "trainingManager"): ?>
								<tr>
									<td><strong>Training Manager For</strong></td>
									<td>
										<div class="associationList">
										<?php
										$trainingManagerAssociations = $userProfileStatistics->getTrainingManagerAssociations();
										
										if(!empty($trainingManagerAssociations) && is_array($trainingManagerAssociations)){
											$trainingManagerAssociations = $userProfile->resolveUserNames($trainingManagerAssociations);
											
											foreach($trainingManagerAssociations as $key => $subordinate){
												echo '<a href="/admin/profile/'.$key.'">'.$subordinate.'</a>';
												echo "<br />\n";
											}
										}
										else{
											echo "No associations in database.";
										}
										?>
										</div>
									</td>
								</tr>
								<?php else: ?>
								<tr>
									<td><strong>Supervisors</strong></td>
									<td>
										<div class="associationList">
										<?php
										$userSupervisors = $userProfileStatistics->getUserSupervisors();
										
										if(!empty($userSupervisors) && is_array($userSupervisors)){
											$userSupervisors = $userProfile->resolveUserNames($userSupervisors);
											
											foreach($userSupervisors as $key => $supervisor){
												echo '<a href="/admin/profile/'.$key.'">'.$supervisor.'</a>';
												echo "<br />\n";
											}
										}
										else{
											echo "No associations in database.";
										}
										?>
										</div>
									</td>
								</tr>
								<tr>
									<td><strong>Training Managers</strong></td>
									<td>
										<div class="associationList">
										<?php
										$userTrainingManagers = $userProfileStatistics->getUserTrainingManagers();
										
										if(!empty($userTrainingManagers) && is_array($userTrainingManagers)){
											$userTrainingManagers = $userProfile->resolveUserNames($userTrainingManagers);
											
											foreach($userTrainingManagers as $key => $trainingManager){
												echo '<a href="/admin/profile/'.$key.'">'.$trainingManager.'</a>';
												echo "<br />\n";
											}
										}
										else{
											echo "No associations in database.";
										}
										?>
										</div>
									</td>
								</tr>
								<?php endif; ?>
							</table>
						</div>
					</section>
				</div>
				<div class="12u">
					<section>
						<header>
							<h2>User History</h2>
						</header>
						<div id="history-tabs">
							<ul>
								<li><a href="#history-tabs-1">Last Ten Tests</a></li>
								<li><a href="#history-tabs-2">Last Ten Incomplete Tests</a></li>
								<li><a href="#history-tabs-3">Last Ten Log Entries</a></li>
							</ul>
							<div id="history-tabs-1">
							<?php 
							$testManager = new testManager($db, $log, $afsc);
							$userTestArray = $testManager->listUserTests($targetUUID,10);
							
							if($userTestArray): ?>
								<div class="tablecloth">
									<table cellspacing="0" cellpadding="0">
										<tr>
											<th>Time Completed</th>
											<th>AFSC</th>
											<th>Score</th>
											<th>&nbsp;</th>
										</tr>
										<?php foreach($userTestArray as $testUUID => $testData): ?>
										<tr>
											<td><?php echo $testData['testTimeCompleted']; ?></td>
											<td><?php if(count($testData['afscList']) > 1){ echo "Multiple"; }else{ echo $afsc->getAFSCName($testData['afscList'][0]); } ?></td>
											<td><?php echo $testData['testScore']; ?></td>
											<td>
												<a href="/test/view/<?php echo $testUUID; ?>">View</a>
											</td>
										</tr>
										<?php endforeach; ?>
									</table>
								</div>
								<div class="text-right text-warning">
									<a href="/admin/user/<?php echo $targetUUID; ?>/delete-tests">Delete All Tests</a>
								</div>
							<?php else: ?>
								<p>This user has no tests to show.</p>
							<?php endif; ?>
		  					</div>
							<div id="history-tabs-2">
							<?php
							$userIncompleteTestArray = $testManager->listUserIncompleteTests($targetUUID,10);
							
							if($userIncompleteTestArray): ?>
								<div class="tablecloth">
									<table cellspacing="0" cellpadding="0">
										<tr>
											<th>Time Started</th>
											<th>Questions Answered</th>
											<th>AFSC</th>
											<th>Combined</th>
											<th>&nbsp;</th>
										</tr>
										<?php foreach($userIncompleteTestArray as $testUUID => $testData): ?>
										<tr>
											<td><?php echo $testData['timeStarted']; ?></td>
											<td><?php echo $testData['questionsAnswered']; ?></td>
											<td><?php if(count($testData['afscList']) > 1){ echo "Multiple"; }else{ echo $afsc->getAFSCName($testData['afscList'][0]); } ?></td>
											<td><?php if($testData['combinedTest']){ echo "Yes"; } else { echo "No"; } ?></td>
											<td>
												<a href="/test/view/<?php echo $testUUID; ?>">View</a>
											</td>
										</tr>
										<?php endforeach; ?>
									</table>
								</div>
								<div class="text-right text-warning">
									<a href="/admin/user/<?php echo $targetUUID; ?>/delete-incomplete-tests">Delete All Incomplete Tests</a>
								</div>
							<?php else: ?>
								<p>This user has no incomplete tests to show.</p>
							<?php endif; ?>
							</div>
							<div id="history-tabs-3">
								<div class="text-right text-warning">
									<a href="/admin/user/<?php echo $targetUUID; ?>/delete-log-entries">Delete All Log Entries</a>
								</div>
							</div>
						</div>
					</section>
				</div>
			</div>
		</div>
		<?php
	}
else:
	?>
	<h1>User Profile List</h1>
	<br />
	<?php
	$alpha = Array(
			1 => "A",
			2 => "B",
			3 => "C",
			4 => "D",
			5 => "E",
			6 => "F",
			7 => "G",
			8 => "H",
			9 => "I",
			10 => "J",
			11 => "K",
			12 => "L",
			13 => "M",
			14 => "N",
			15 => "O",
			16 => "P",
			17 => "Q",
			18 => "R",
			19 => "S",
			20 => "T",
			21 => "U",
			22 => "V",
			23 => "W",
			24 => "X",
			25 => "Y",
			26 => "Z");
	
	$userList = $user->listUsers();
	$userCount = count($userList) + 1;
	
	if($userList): ?>
		<h2><?php echo $userCount; ?> Total Users</h2>
		<div class="container">
			<div class="row">
				<div class="8u">
					<section>
						<p>
						<?php
						foreach($alpha as $val){
							echo ' <a href="#'.$val.'">'.$val.'</a> ';
						}
						?>
						</p>
					</section>
				</div>
			</div>
		</div>
		<div class="container">
			<div class="row">
				<div class="4u">
					<section>
					<br />
					<?php
					$curLetter = "";
					
					$firstColComplete = false;
					$secondColComplete = false;
					
					foreach($userList as $uuid => $userRow){
						$letter = substr($userRow['userLastName'],0,1);
						
						if($letter == "H" && $firstColComplete == false){ ?>
							</section>
							</div>
							<div class="4u">
							<section>
							<br />
							<?php
							$firstColComplete = true;
						}
						
						if($letter == "Q" && $secondColComplete == false){ ?>
							</section>
							</div>
							<div class="4u">
							<section>
							<br />
							<?php
							$secondColComplete = true;
						}
						
						if($letter != $curLetter){
							echo '<h2><a id="'.ucfirst($letter).'">'.$letter.'</a></h2>';
							$curLetter = $letter;
						}
						
						echo '<a href="/admin/profile/'.$uuid.'">'.$userRow['userLastName'].', '.$userRow['userFirstName'].' '.$userRow['userRank'].'</a><br />';
					}
					?>
					</section>
				</div>
			</div>
		</div>
		<?php
	else:
		echo "There are no users in the database.";
	endif;
endif;
?>