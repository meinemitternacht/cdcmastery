<?php
$targetUUID = $_SESSION['userUUID'];
$userProfile = new CDCMastery\UserManager($db, $systemLog, $emailQueue);
$userProfileStatistics = new CDCMastery\UserStatisticsModule($db, $systemLog, $roleManager, $memcache);

if(!$userProfile->loadUser($targetUUID)){
	echo "That user does not exist.";
}
else{
	$userProfileStatistics->setUserUUID($targetUUID);
	?>
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
		table#profile-table-1 td:nth-of-type(1):before { content: "Completed"; }
		table#profile-table-1 td:nth-of-type(2):before { content: "AFSC"; }
		table#profile-table-1 td:nth-of-type(3):before { content: "Score"; }
		table#profile-table-1 td:nth-of-type(4):before { content: "Actions"; }
		
		table#profile-table-2 td:nth-of-type(1):before { content: "Started"; }
		table#profile-table-2 td:nth-of-type(2):before { content: "Progress"; }
		table#profile-table-2 td:nth-of-type(3):before { content: "AFSC"; }
		table#profile-table-2 td:nth-of-type(4):before { content: "Combined Test"; }
		table#profile-table-2 td:nth-of-type(5):before { content: "Actions"; }
		
		
		table#profile-table-3 td:nth-of-type(1):before { content: "Timestamp"; }
		table#profile-table-3 td:nth-of-type(2):before { content: "Action"; }
		table#profile-table-3 td:nth-of-type(3):before { content: "IP"; }
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
	<script>
	$(function() {
		$( "#history-tabs" ).tabs();
	});
	</script>
	<div class="container">
		<div class="row">
			<div class="12u">
				<section>
                    <header>
                        <h2><?php echo $userProfile->getFullName(); ?></h2>
                    </header>
                    <a href="/user/edit" class="button">Edit Profile</a>
					<a href="/user/afsc-associations" class="button">Manage AFSC's</a>
					<a href="/user/top-missed" class="button">Top Missed Questions</a>
                </section>
            </div>
        </div>
        <div class="row">
            <div class="6u">
                <section>
        			<table>
						<tr>
							<th colspan="2">Account Details</th>
						</tr>
						<tr>
							<th class="th-child">Base</th>
							<td><?php echo $baseManager->getBaseName($userProfile->getUserBase()); ?></td>
						</tr>
						<tr>
							<th class="th-child">Office Symbol</th>
							<td><?php if($userProfile->getUserOfficeSymbol()){ echo $officeSymbolManager->getOfficeSymbol($userProfile->getUserOfficeSymbol()); } else { echo "N/A"; } ?></td>
						</tr>
						<tr>
							<th class="th-child">Date Registered</th>
							<td><time class="timeago" datetime="<?php echo $cdcMastery->formatDateTime($userProfile->getUserDateRegistered(),"c"); ?>"><?php echo $cdcMastery->formatDateTime($userProfile->getUserDateRegistered()); ?> UTC</time></td>
						</tr>
						<tr>
							<th class="th-child">Last Login</th>
							<td><time class="timeago" datetime="<?php echo $cdcMastery->formatDateTime($userProfile->getUserLastLogin(),"c"); ?>"><?php echo $cdcMastery->formatDateTime($userProfile->getUserLastLogin()); ?> UTC</time></td>
						</tr>
						<tr>
							<th class="th-child">Last Active</th>
							<td><time class="timeago" datetime="<?php echo $cdcMastery->formatDateTime($userProfile->getUserLastActive(),"c"); ?>"><?php echo $cdcMastery->formatDateTime($userProfile->getUserLastActive()); ?> UTC</time></td>
						</tr>
						<tr>
							<th class="th-child">Role</th>
							<td>
								<?php echo $roleManager->getRoleName($userProfile->getUserRole()); ?>
								<?php if($userProfile->getUserRole() == $roleManager->getRoleUUIDByName("Users") || $userProfile->getUserRole() == $roleManager->getRoleUUIDByName("Supervisors")): ?>
								&nbsp;<span class="text-warning"><a href="/user/request-role">Change Role &raquo;</a></span>
								<?php endif; ?>
							</td>
						</tr>
						<tr>
							<th class="th-child">E-Mail</th>
							<td><?php echo $userProfile->getUserEmail(); ?></td>
						</tr>
						<tr>
							<th class="th-child">Time Zone</th>
							<td><?php echo $userProfile->getUserTimeZone(); ?></td>
						</tr>
						<tr>
							<th class="th-child">Username</th>
							<td><?php echo $userProfile->getUserHandle(); ?></td>
						</tr>
						<tr>
							<th class="th-child">Associated With</th>
							<td>
								<?php
								$userAFSCList = $userProfileStatistics->getAFSCAssociations();

								if(!$userAFSCList){
									echo "No associations.<br>";
								}
								else{
									foreach($userAFSCList as $userAFSC){
                                        echo $userAFSC . "<br>";
									}
								}
								?>
                                <a href="/user/afsc-associations">Manage AFSC's</a>
							</td>
						</tr>
						<tr>
							<th class="th-child">Pending Associations</th>
							<td>
								<?php
								$userPendingAFSCList = $userProfileStatistics->getPendingAFSCAssociations();

                                if(!$userPendingAFSCList){
                                    echo "No pending associations.";
                                }
                                else{
                                    foreach($userPendingAFSCList as $userAFSCuuid => $afscData){
                                        echo $afscManager->getAFSCName($userAFSCuuid)."<br />";
                                    }
                                }
								?>
                                <a href="/user/afsc-associations">Manage AFSC's</a>
							</td>
						</tr>
					</table>
				</section>
			</div>
			<div class="6u">
				<section>
					<table>
						<tr>
							<th colspan="2">General Statistics</th>
						</tr>
						<tr>
							<th class="th-child">Log Entries</th>
							<td><?php echo number_format($userProfileStatistics->getLogEntries()); ?> <a href="/user/log">View &raquo;</a></td>
						</tr>
						<tr>
							<th colspan="2">Testing Statistics</th>
						</tr>
						<tr>
							<th class="th-child">Average Score</th>
							<td><?php echo $userProfileStatistics->getAverageScore(); ?></td>
						</tr>
                        <tr>
                            <th class="th-child">Completed Tests</th>
                            <td><?php echo number_format($userProfileStatistics->getCompletedTests()); ?></td>
                        </tr>
                        <tr>
                            <th class="th-child">Incomplete Tests</th>
                            <td><?php echo number_format($userProfileStatistics->getIncompleteTests()); ?></td>
                        </tr>
                        <tr>
                            <th class="th-child">Total Tests</th>
                            <td><?php echo number_format($userProfileStatistics->getTotalTests()); ?></td>
                        </tr>
                        <tr>
                            <th class="th-child">Questions Answered</th>
                            <td><?php echo number_format($userProfileStatistics->getQuestionsAnswered()); ?></td>
                        </tr>
                        <tr>
                            <th class="th-child">Questions Missed</th>
                            <td><?php echo number_format($userProfileStatistics->getQuestionsMissed()); ?></td>
                        </tr>
						<tr>
							<th colspan="2">User Associations</th>
						</tr>
						<?php
						$userRole = $roleManager->verifyUserRole($targetUUID);
						if($userRole == "supervisor"): ?>
						<tr>
							<th class="th-child">Supervisor For</th>
							<td>
								<div class="associationList">
								<?php
								$supervisorAssociations = $userProfileStatistics->getSupervisorAssociations();
								
								if(!empty($supervisorAssociations) && is_array($supervisorAssociations)){
									$supervisorAssociations = $userProfile->resolveUserNames($supervisorAssociations);
									
									foreach($supervisorAssociations as $key => $subordinate){
										if($cdcMastery->verifyAdmin() || $cdcMastery->verifyTrainingManager() || $cdcMastery->verifySupervisor()) {
											echo '<a href="/admin/profile/' . $key . '">' . $subordinate . '</a>';
										}
										else{
											echo $subordinate;
										}
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
							<th class="th-child">Training Manager For</th>
							<td>
								<div class="associationList">
								<?php
								$trainingManagerAssociations = $userProfileStatistics->getTrainingManagerAssociations();
								
								if(!empty($trainingManagerAssociations) && is_array($trainingManagerAssociations)){
									$trainingManagerAssociations = $userProfile->resolveUserNames($trainingManagerAssociations);
									
									foreach($trainingManagerAssociations as $key => $subordinate){
										if($cdcMastery->verifyAdmin() || $cdcMastery->verifyTrainingManager()) {
											echo '<a href="/admin/profile/' . $key . '">' . $subordinate . '</a>';
										}
										else{
											echo $subordinate;
										}
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
							<th class="th-child">Supervisors</th>
							<td>
								<div class="associationList">
								<?php
								$userSupervisors = $userProfileStatistics->getUserSupervisors();
								
								if(!empty($userSupervisors) && is_array($userSupervisors)){
									$userSupervisors = $userProfile->resolveUserNames($userSupervisors);
									
									foreach($userSupervisors as $key => $supervisor){
										if($cdcMastery->verifyAdmin() || $cdcMastery->verifyTrainingManager()) {
											echo '<a href="/admin/profile/' . $key . '">' . $supervisor . '</a>';
										}
										else{
											echo $supervisor;
										}
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
							<th class="th-child">Training Managers</th>
							<td>
								<div class="associationList">
								<?php
								$userTrainingManagers = $userProfileStatistics->getUserTrainingManagers();
								
								if(!empty($userTrainingManagers) && is_array($userTrainingManagers)){
									$userTrainingManagers = $userProfile->resolveUserNames($userTrainingManagers);
									
									foreach($userTrainingManagers as $key => $trainingManager){
										if($cdcMastery->verifyAdmin() || $cdcMastery->verifyTrainingManager()) {
											echo '<a href="/admin/profile/' . $key . '">' . $trainingManager . '</a>';
										}
										else{
											echo $trainingManager;
										}
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
				</section>
			</div>
		</div>
		<div class="row">
			<div class="12u">
				<section>
					<header>
						<h2>Your History</h2>
					</header>
					<div id="history-tabs">
						<ul>
							<li><a href="#history-tabs-1">Last Ten Tests</a></li>
							<li><a href="#history-tabs-2">Last Five Incomplete Tests</a></li>
							<li><a href="#history-tabs-3">Last Ten Log Entries</a></li>
						</ul>
						<div id="history-tabs-1">
						<?php 
						$testManager = new CDCMastery\TestManager($db, $systemLog, $afscManager);
						$userTestArray = $testManager->listUserTests($targetUUID,10);
						
						if($userTestArray): ?>
							<table class="tableSmallText" id="profile-table-1">
								<thead>
									<tr>
										<th>Time Completed</th>
										<th>AFSC</th>
										<th>Score</th>
										<th>&nbsp;</th>
									</tr>
								</thead>
								<tbody>
								<?php foreach($userTestArray as $testUUID => $testData): ?>
									<tr>
										<td><?php echo $cdcMastery->outputDateTime($testData['testTimeCompleted'],$_SESSION['timeZone']); ?></td>
										<td title="<?php array_walk_recursive($testData['afscList'],array($afscManager,'getAFSCNameCallback')); echo implode(", ", $testData['afscList']); ?>"><?php if(count($testData['afscList']) > 1){ echo "Multiple (hover to view)"; }else{ echo $testData['afscList'][0]; } ?></td>
										<td><?php echo $testData['testScore']; ?></td>
										<td>
											<a href="/test/view/<?php echo $testUUID; ?>">View</a>
										</td>
									</tr>
								<?php endforeach; ?>
								</tbody>
							</table>
						<?php else: ?>
							<p>You have no tests to show.</p>
						<?php endif; ?>
	  					</div>
						<div id="history-tabs-2">
						<?php
						$userIncompleteTestArray = $testManager->listUserIncompleteTests($targetUUID,5);
						
						if($userIncompleteTestArray): ?>
							<table class="tableSmallText" id="profile-table-2">
								<thead>
									<tr>
										<th>Time Started</th>
										<th>Questions Answered</th>
										<th>Total Questions</th>
										<th>AFSC</th>
										<th>Combined</th>
									</tr>
								</thead>
								<tbody>
								<?php foreach($userIncompleteTestArray as $testUUID => $testData): ?>
									<tr>
										<td><?php echo $cdcMastery->outputDateTime($testData['timeStarted'],$_SESSION['timeZone']); ?></td>
										<td><?php echo $testData['questionsAnswered']; ?></td>
										<td><?php echo $testData['totalQuestions']; ?></td>
										<td title="<?php array_walk_recursive($testData['afscList'],array($afscManager,'getAFSCNameCallback')); echo implode(", ", $testData['afscList']); ?>"><?php if(count($testData['afscList']) > 1){ echo "Multiple (hover to view)"; }else{ echo $testData['afscList'][0]; } ?></td>
										<td><?php if($testData['combinedTest']){ echo "Yes"; } else { echo "No"; } ?></td>
									</tr>
								<?php endforeach; ?>
								</tbody>
							</table>
						<?php else: ?>
							<p>You have no incomplete tests to show.</p>
						<?php endif; ?>
						</div>
						<div id="history-tabs-3">
						<?php if($userProfileStatistics->getLogEntries() > 0): ?>
							<table class="tableSmallText" id="profile-table-3">
								<thead>
									<tr>
										<th>Timestamp</th>
										<th>Action</th>
										<th>IP</th>
									</tr>
								</thead>
								<tbody>
									<?php 
									$logFilter = new CDCMastery\SystemLogFilter($db, $userManager);
									$logFilter->setFilterUserUUID($targetUUID);
									$logFilter->setPageRows(10);
									$logFilter->setRowOffset(0);
									$logEntries = $logFilter->listEntries();
									
									foreach($logEntries as $logUUID => $logData): ?>
									<tr>
										<td><?php echo $cdcMastery->outputDateTime($logData['timestamp'], $_SESSION['timeZone']); ?></td>
										<td><?php echo $logData['action']; ?></td>
										<td><?php echo $logData['ip']; ?></td>
									</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
						<?php else: ?>
							<p>You have no log entries in the system.</p>
						<?php endif; ?>
						</div>
					</div>
				</section>
			</div>
		</div>
	</div>
<?php 
}
?>