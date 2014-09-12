<?php
if(isset($_SESSION['vars'][0])):
	$targetUUID = $_SESSION['vars'][0];
	$userProfile = new user($db, $log);
	if(!$userProfile->loadUser($targetUUID)){
		echo "That user does not exist.";
	}
	else{
		$userStatistics->setUserUUID($targetUUID);
		?>
		<a href="/admin/profile">&laquo; return to user list</a>
		<br />
		<br />
		<h2><?php echo $userProfile->getFullName(); ?></h2>
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
									<th colspan="2">AFSC Associations</th>
								</tr>
								<tr>
									<td><strong>Associated With</strong></td>
									<td>
										<?php
										$afscList = $userStatistics->getAFSCAssociations();
										
										foreach($afscList as $userAFSC){
											echo $afsc->getAFSCName($userAFSC)."<br />";
										}
										?>
									</td>
								</tr>
								<tr>
									<td><strong>Pending Associations</strong></td>
									<td>
										<?php
										$afscList = $userStatistics->getPendingAFSCAssociations();
										
										foreach($afscList as $userAFSC){
											echo $afsc->getAFSCName($userAFSC)."<br />";
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
									<td><?php echo $userStatistics->getLogEntries(); ?></td>
								</tr>
								<tr>
									<th colspan="2">Testing Statistics</th>
								</tr>
								<tr>
									<td><strong>Average Score</strong></td>
									<td><?php echo $userStatistics->getAverageScore(); ?></td>
								</tr>
								<tr>
									<td><strong>Completed Tests</strong></td>
									<td><?php echo $userStatistics->getCompletedTests(); ?></td>
								</tr>
								<tr>
									<td><strong>Incomplete Tests</strong></td>
									<td><?php echo $userStatistics->getIncompleteTests(); ?></td>
								</tr>
								<tr>
									<td><strong>Total Tests</strong></td>
									<td><?php echo $userStatistics->getTotalTests(); ?></td>
								</tr>
								<tr>
									<td><strong>Questions Answered</strong></td>
									<td><?php echo $userStatistics->getQuestionsAnswered(); ?></td>
								</tr>
								<tr>
									<td><strong>Questions Missed</strong></td>
									<td><?php echo $userStatistics->getQuestionsMissed(); ?></td>
								</tr>
								<tr>
									<th colspan="2">User Associations</th>
								</tr>
								<?php
								$userRole = $roles->verifyUserRole($targetUUID);
								if($userRole == "supervisor"): ?>
								<tr>
									<td><strong>Supervisor For</strong></td>
									<td>
										<div class="associationList">
										<?php
										$supervisorAssociations = $userStatistics->getSupervisorAssociations();
										
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
										$trainingManagerAssociations = $userStatistics->getTrainingManagerAssociations();
										
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
										$userSupervisors = $userStatistics->getUserSupervisors();
										
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
										$userTrainingManagers = $userStatistics->getUserTrainingManagers();
										
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