<?php
if(isset($_SESSION['vars'][0])):
	if(!$cdcMastery->verifySupervisor() && !$cdcMastery->verifyAdmin()){
		$systemMessages->addMessage("You are not authorized to use the Supervisor log page.", "danger");
		$cdcMastery->redirect("/errors/403");
	}

	$supUser = new UserManager($db, $systemLog, $emailQueue);
	$supOverview = new SupervisorOverview($db, $systemLog, $userStatistics, $supUser, $roleManager);

	$supOverview->loadSupervisor($_SESSION['userUUID']);

	$subordinateUsers = $supOverview->getSubordinateUserList();

	if(empty($subordinateUsers)):
		$systemMessages->addMessage("You do not have any subordinate users. Please associate users with your account using the form below.", "info");
		$cdcMastery->redirect("/supervisor/subordinates");
	endif;

	$logUUID = isset($_SESSION['vars'][0]) ? $_SESSION['vars'][0] : false;

	if($logUUID):
		if($systemLog->verifyLogUUID($logUUID)):
			$logData = new SystemLog($db);
			$logData->loadEntry($logUUID);

			if(!in_array($logData->getUserUUID(),$subordinateUsers)){
				$systemMessages->addMessage("That user is not associated with your account.", "danger");
				$cdcMastery->redirect("/supervisor/overview");
			}

			$logDetails = $logData->fetchDetails($logUUID); ?>
			<div class="container">
				<div class="row">
					<div class="4u">
						<div class="sub-menu">
							<ul>
								<li><a href="/supervisor/log/<?php echo $logData->getUserUUID(); ?>" title="Return to Log"><i class="icon-inline icon-20 ic-arrow-left"></i>Return to Log</a></li>
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
									<td><?php echo $userManager->getUserByUUID($logData->getUserUUID()); ?></td>
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
								<?php foreach($logDetails as $detailKey => $detailData): ?>
								<tr>
									<td><?php echo $detailData['dataType']; ?></td>
									<td><?php echo $detailData['data']; ?></td>
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
			$systemMessages->addMessage("That log entry does not exist.", "warning");
			$cdcMastery->redirect("/supervisor/overview");
		endif;
	else:
		$systemMessages->addMessage("No log entry specified.", "warning");
		$cdcMastery->redirect("/supervisor/overview");
	endif;
else:
	$systemMessages->addMessage("You must select a user log to view.", "warning");
	$cdcMastery->redirect("/supervisor/overview");
endif;
?>