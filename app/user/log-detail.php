<?php
$logUUID = isset($_SESSION['vars'][0]) ? $_SESSION['vars'][0] : false;

if($logUUID):
	if($systemLog->verifyLogUUID($logUUID)): 
		$logData = new SystemLog($db);
		$logData->loadEntry($logUUID);
		$logDetails = $logData->fetchDetails($logUUID); ?>
		<div class="container">
			<div class="row">
				<div class="4u">
					<div class="sub-menu">
						<ul>
							<li><a href="/user/log" title="First"><i class="icon-inline icon-20 ic-arrow-left"></i>Return to Log</a></li>
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
								<td><?php echo $logUUID; ?></td>
							</tr>
							<tr>
								<th>User</th>
								<td><?php echo $userManager->getUserByUUID($logData->getUserUUID()); ?></td>
							</tr>
							<tr>
								<th>Action</th>
								<td><span class="<?php echo $logData->getRowStyle($logData->getAction()); ?>"><?php echo $logData->getAction(); ?></span></td>
							</tr>
							<tr>
								<th>IP</th>
								<td><?php echo $logData->getIP(); ?></td>
							</tr>
							<tr>
								<th>Timestamp</th>
								<td><?php echo $cdcMastery->outputDateTime($logData->getTimestamp(), $_SESSION['timeZone']); ?></td>
							</tr>
							<?php if(!empty($logData->getUserAgent())): ?>
							<tr>
								<th>User Agent (Browser)</th>
								<td><?php echo $logData->getUserAgent(); ?></td>
							</tr>
							<?php endif; ?>
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
											$userName = $userManager->getUserNameByUUID($detailData['data']);
										endif;
									endif;
							?>
							<tr>
								<td><?php echo $detailData['dataType']; ?></td>
								<?php if(isset($userName) && !empty($userName)): ?>
									<td>
										<?php echo $userName; ?>
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
		$cdcMastery->redirect("/user/log");
	endif;
else:
	$cdcMastery->redirect("/user/log");
endif;