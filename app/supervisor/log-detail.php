<?php
if(isset($_SESSION['vars'][0])):
	$targetUUID = $_SESSION['vars'][0];

	if(!$cdcMastery->verifySupervisor() && !$cdcMastery->verifyAdmin()){
		$sysMsg->addMessage("You are not authorized to use the Supervisor user log page.");
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

	$logUUID = isset($_SESSION['vars'][1]) ? $_SESSION['vars'][1] : false;

	if($logUUID):
		if($log->verifyLogUUID($logUUID)):
			$logData = new log($db);
			$logData->loadEntry($logUUID);
			$logDetails = $logData->fetchDetails($logUUID); ?>
			<div class="container">
				<div class="row">
					<div class="4u">
						<div class="sub-menu">
							<ul>
								<li><a href="/supervisor/log/<?php echo $targetUUID; ?>" title="First"><i class="icon-inline icon-20 ic-arrow-left"></i>Return to Log</a></li>
							</ul>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="6u">
						<section>
							<header>
								<h2>Log Entry Details</h2>
							</header>
							<br>
							<table>
								<tr>
									<th>UUID</th>
									<td><?php echo $logUUID; ?>
								</tr>
								<tr>
									<th>User</th>
									<td><?php echo $user->getUserByUUID($logData->getUserUUID()); ?></td>
								</tr>
								<tr>
									<th>Action</th>
									<td><?php echo $logData->getAction(); ?></td>
								</tr>
								<tr>
									<th>IP</th>
									<td><?php echo $logData->getIP(); ?></td>
								</tr>
								<tr>
									<th>Timestamp</th>
									<td><?php echo $cdcMastery->outputDateTime($logData->getTimestamp(), $_SESSION['timeZone']); ?>
								</tr>
							</table>
						</section>
					</div>
				</div>
				<?php if(!empty($logDetails)): ?>
				<div class="row">
					<div class="8u">
						<section>
							<header>
								<h2>Attached Data</h2>
							</header>
							<br>
							<table>
								<tr>
									<th>Key</th>
									<th>Data</th>
								</tr>
								<?php foreach($logDetails as $detailKey => $detailData):
										$dataTypeSearch = strtolower($detailData['dataType']);

										if(	strpos($dataTypeSearch,"user") !== false ||
											strpos($dataTypeSearch,"supervisor") !== false ||
											strpos($dataTypeSearch,"training manager") !== false):
											if(strpos($dataTypeSearch,"uuid") !== false):
												$userName = $user->getUserNameByUUID($detailData['data']);
											endif;
										endif;
								?>
								<tr>
									<td><?php echo $detailData['dataType']; ?></td>
									<?php if(isset($userName) && !empty($userName)): ?>
										<td>
											<a href="/admin/users/<?php echo $detailData['data']; ?>"><?php echo $userName; ?></a>
										</td>
									<?php else: ?>
										<td><?php echo $detailData['data']; ?></td>
									<?php endif; ?>
								</tr>
								<?php endforeach; ?>
							</table>
						</section>
					</div>
				</div>
				<?php endif; ?>
			</div>
		<?php
		else:
			$sysMsg->addMessage("That log entry does not exist.");
			$cdcMastery->redirect("/supervisor/overview");
		endif;
	else:
		$sysMsg->addMessage("No log entry specified.");
		$cdcMastery->redirect("/supervisor/overview");
	endif;
else:
	$sysMsg->addMessage("You must select a user log to view.");
	$cdcMastery->redirect("/supervisor/overview");
endif;
?>