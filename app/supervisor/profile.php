<?php
if(isset($_SESSION['vars'][0])):
	$targetUUID = $_SESSION['vars'][0];

    if(!$cdcMastery->verifySupervisor() && !$cdcMastery->verifyAdmin()){
        $sysMsg->addMessage("You are not authorized to use the Supervisor profile page.");
        $cdcMastery->redirect("/errors/403");
    }

    $supUser = new user($db,$log,$emailQueue);
    $supOverview = new supervisorOverview($db,$log,$userStatistics,$supUser,$roles);

    $supOverview->loadSupervisor($_SESSION['userUUID']);

    $subordinateUsers = $user->sortUserUUIDList($supOverview->getSubordinateUserList(),"userLastName");

    if(empty($subordinateUsers)):
        $sysMsg->addMessage("You do not have any subordinate users.");
        $cdcMastery->redirect("/supervisor/subordinates");
    endif;

    if(!in_array($targetUUID,$subordinateUsers)){
        $sysMsg->addMessage("That user is not associated with your account.");
        $cdcMastery->redirect("/supervisor/overview");
    }

	$userProfile = new user($db, $log, $emailQueue);
	$userProfileStatistics = new userStatistics($db, $log, $roles);
	if(!$userProfile->loadUser($targetUUID)){
		$sysMsg->addMessage("That user does not exist.");
	}
	else{
		$userProfileStatistics->setUserUUID($targetUUID);
		?>
		<!--[if !IE]><!-->
		<style type="text/css">
		@media
		only screen and (max-width: 760px),
		(min-device-width: 768px) and (max-device-width: 1024px)  {
			table, thead, tbody, th, td, tr {
				display: block;
			}

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
			table#profile-table-1 td:nth-of-type(1):before { content: "Started"; }
			table#profile-table-1 td:nth-of-type(2):before { content: "Progress"; }
			table#profile-table-1 td:nth-of-type(3):before { content: "AFSC"; }
			table#profile-table-1 td:nth-of-type(4):before { content: "Actions"; }
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
						<a href="/supervisor/overview" class="button">&laquo; Back</a>
					</section>
				</div>
			</div>
			<div class="row">
				<div class="6u">
					<section>
						<table>
							<tr>
								<th colspan="2">User Information</th>
							</tr>
							<tr>
								<th class="th-child">Base</th>
								<td><?php echo $bases->getBaseName($userProfile->getUserBase()); ?></td>
							</tr>
							<tr>
								<th class="th-child">Office Symbol</th>
								<td><?php if($userProfile->getUserOfficeSymbol()){ echo $officeSymbol->getOfficeSymbol($userProfile->getUserOfficeSymbol()); } else { echo "N/A"; } ?></td>
							</tr>
							<tr>
								<th class="th-child">Date Registered</th>
								<td><?php echo $userProfile->getUserDateRegistered(); ?></td>
							</tr>
							<tr>
								<th class="th-child">Last Login</th>
								<td><?php echo $userProfile->getUserLastLogin(); ?></td>
							</tr>
							<tr>
								<th class="th-child">Role</th>
								<td><?php echo $roles->getRoleName($userProfile->getUserRole()); ?></td>
							</tr>
							<tr>
								<th colspan="2">Personal Details</th>
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
								<th colspan="2"><div class="text-float-left">AFSC Associations</div><div class="text-float-right text-white"></div></th>
							</tr>
							<tr>
								<th class="th-child">Associated With</th>
								<td>
									<?php
									$userAFSCList = $userProfileStatistics->getAFSCAssociations();
									
									if(!$userAFSCList){
										echo "No associations.";
									}
									else{
										foreach($userAFSCList as $userAFSCuuid => $afscData){
											echo $afsc->getAFSCName($userAFSCuuid)."<br>";
										}
									}
									?>
								</td>
							</tr>
							<tr>
								<th class="th-child">Pending Associations</th>
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
					</section>
				</div>
				<div class="6u">
					<section>
						<table cellspacing="0" cellpadding="0">
							<tr>
								<th colspan="2">General Statistics</th>
							</tr>
							<tr>
								<th class="th-child">Log Entries</th>
								<td><div class="text-float-left"><?php echo $userProfileStatistics->getLogEntries(); ?></div><div class="text-float-right"><a href="/supervisor/log/<?php echo $targetUUID; ?>">View &raquo;</a></div></td>
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
								<td><?php echo $userProfileStatistics->getCompletedTests(); ?></td>
							</tr>
							<tr>
								<th class="th-child">Incomplete Tests</th>
								<td><?php echo $userProfileStatistics->getIncompleteTests(); ?></td>
							</tr>
							<tr>
								<th class="th-child">Total Tests</th>
								<td><?php echo $userProfileStatistics->getTotalTests(); ?></td>
							</tr>
							<tr>
								<th class="th-child">Questions Answered</th>
								<td><?php echo $userProfileStatistics->getQuestionsAnswered(); ?></td>
							</tr>
							<tr>
								<th class="th-child">Questions Missed</th>
								<td><?php echo $userProfileStatistics->getQuestionsMissed(); ?></td>
							</tr>
							<tr>
								<th colspan="2"><div class="text-float-left">User Associations</div></th>
							</tr>
							<?php
							$userRole = $roles->verifyUserRole($targetUUID);
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
											echo '<a href="/supervisor/profile/'.$key.'">'.$subordinate.'</a>';
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
											echo '<a href="/supervisor/profile/'.$key.'">'.$subordinate.'</a>';
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
											echo '<a href="/supervisor/profile/'.$key.'">'.$supervisor.'</a>';
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
											echo '<a href="/supervisor/profile/'.$key.'">'.$trainingManager.'</a>';
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
                                <li><a href="#history-tabs-4">IP Addresses</a></li>
							</ul>
							<div id="history-tabs-1">
							<?php 
							$testManager = new testManager($db, $log, $afsc);
							$userTestArray = $testManager->listUserTests($targetUUID,10);
							
							if($userTestArray): ?>
								<table cellspacing="0" cellpadding="0">
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
											<td><?php echo $testData['testTimeCompleted']; ?></td>
											<td><?php if(count($testData['afscList']) > 1){ echo "Multiple"; }else{ echo $afsc->getAFSCName($testData['afscList'][0]); } ?></td>
											<td><?php echo $testData['testScore']; ?></td>
											<td>
												<a href="/test/view/<?php echo $testUUID; ?>">View</a>
											</td>
										</tr>
									<?php endforeach; ?>
									</tbody>
								</table>
							<?php else: ?>
								<p>This user has no tests to show.</p>
							<?php endif; ?>
		  					</div>
							<div id="history-tabs-2">
							<?php
							$userIncompleteTestArray = $testManager->listUserIncompleteTests($targetUUID,10);
							
							if($userIncompleteTestArray): ?>
								<table cellspacing="0" cellpadding="0">
									<thead>
										<tr>
											<th>Time Started</th>
											<th>Questions Answered</th>
											<th>AFSC</th>
											<th>Combined</th>
											<th>&nbsp;</th>
										</tr>
									</thead>
									<tbody>
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
									</tbody>
								</table>
							<?php else: ?>
								<p>This user has no incomplete tests to show.</p>
							<?php endif; ?>
							</div>
							<div id="history-tabs-3">
							<?php if($userProfileStatistics->getLogEntries() > 0): ?>
								<table>
									<tr>
										<th>Timestamp</th>
										<th>Action</th>
										<th>IP</th>
										<th>&nbsp;</th>
									</tr>
									<?php 
									$logFilter = new logFilter($db, $user);
									$logFilter->setFilterUserUUID($targetUUID);
									$logFilter->setPageRows(10);
									$logFilter->setRowOffset(0);
									$logEntries = $logFilter->listEntries();
									
									foreach($logEntries as $logUUID => $logData): ?>
									<tr>
										<td><?php echo $cdcMastery->outputDateTime($logData['timestamp'], $_SESSION['timeZone']); ?></td>
										<td><?php echo $logData['action']; ?></td>
										<td><?php echo $logData['ip']; ?></td>
										<td><a href="/supervisor/log-detail/<?php echo $logUUID; ?>/profile"><i class="icon-inline icon-20 ic-arrow-right"></i>details</a></td>
									</tr>
									<?php endforeach; ?>
								</table>
							<?php else: ?>
								<p>This user has no log entries in the system.</p>
							<?php endif; ?>
							</div>
							<div id="history-tabs-4">
                                <?php $ipAddressList = $userProfileStatistics->getIPAddresses(); ?>
								<?php if(is_array($ipAddressList) && count($ipAddressList) > 0): ?>
                                    <a href="/supervisor/profile/<?php echo $targetUUID; ?>?resolve">Resolve IP Addresses</a> (This may take a long time to load)
									<table>
										<tr>
											<th>IP Address</th>
                                            <?php if(isset($_GET['resolve'])): ?>
                                            <th>Reverse DNS</th>
                                            <?php endif; ?>
										</tr>
                                        <?php foreach($ipAddressList as $ipAddress): ?>
                                        <tr>
                                            <td><?php echo $ipAddress; ?></td>
                                            <?php if(isset($_GET['resolve'])): ?>
                                            <td><?php if(!empty($ipAddress)) echo gethostbyaddr($ipAddress); ?></td>
                                            <?php endif; ?>
                                        </tr>
                                        <?php endforeach; ?>
									</table>
								<?php else: ?>
									<p>This user has no IP addresses logged in the system.</p>
								<?php endif; ?>
							</div>
						</div>
					</section>
				</div>
			</div>
		</div>
		<?php
	}
else:
    $sysMsg->addMessage("You must select a user profile to view.");
    $cdcMastery->redirect("/supervisor/overview");
endif;
?>