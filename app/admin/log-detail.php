<?php
$logUUID = isset($_SESSION['vars'][0]) ? $_SESSION['vars'][0] : false;
$returnPath = isset($_SESSION['vars'][1]) ? strtolower($_SESSION['vars'][1]) : "log";

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
							<?php if($returnPath == "log"): ?>
							<li><a href="/admin/log" title="First"><i class="icon-inline icon-20 ic-arrow-left"></i>Return to Log</a></li>
							<?php elseif($returnPath == "user-log"): ?>
							<li><a href="/admin/users/<?php echo $logData->getUserUUID(); ?>/log" title="First"><i class="icon-inline icon-20 ic-arrow-left"></i>Return to User Manager</a></li>
							<?php elseif($returnPath == "profile"): ?>
							<li><a href="/admin/profile/<?php echo $logData->getUserUUID(); ?>" title="First"><i class="icon-inline icon-20 ic-arrow-left"></i>Return to User Profile</a></li>
							<?php endif; ?>
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
		$cdcMastery->redirect("/admin/log");
	endif;
else:
	$cdcMastery->redirect("/admin/log");
endif;