<?php
$testManager = new testManager($db,$log,$afsc);

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
								<td><?php echo $logUUID; ?></td>
							</tr>
							<tr>
								<th>User</th>
								<?php if($logData->getUserUUID() == "ANONYMOUS" || $logData->getUserUUID() == "SYSTEM"): ?>
								<td><?php echo $logData->getUserUUID(); ?></td>
								<?php else: ?>
								<td><a href="/admin/profile/<?php echo $logData->getUserUUID(); ?>" title="View Profile"><?php echo $user->getUserByUUID($logData->getUserUUID()); ?></a></td>
								<?php endif; ?>
							</tr>
							<tr>
								<th>Action</th>
								<td><?php echo $logData->getAction(); ?></td>
							</tr>
							<tr>
								<th>IP</th>
								<td><a href="/admin/log/0/25/timestamp/DESC/ip/<?php echo base64_encode($logData->getIP()); ?>" title="Show log entries for this IP"><?php echo $logData->getIP(); ?></a></td>
							</tr>
							<tr>
								<th>Timestamp</th>
								<td><?php echo $cdcMastery->outputDateTime($logData->getTimestamp(), $_SESSION['timeZone']); ?></td>
							</tr>
							<tr>
								<th>Microtime</th>
								<td><?php echo $logData->getMicrotime(); ?></td>
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
										strpos($dataTypeSearch,"training manager") !== false) {
										if (strpos($dataTypeSearch, "uuid") !== false) {
											$userName = $user->getUserNameByUUID($detailData['data']);
										}
									}
									elseif($dataTypeSearch == "afsc array"){
										$afscArray = unserialize($detailData['data']);

										foreach($afscArray as $dataAFSCUUID){
											$afscList[] = '<a href="/admin/cdc-data/'.$dataAFSCUUID.'">'.$afsc->getAFSCName($dataAFSCUUID).'</a>';
										}

										if(count($afscList) > 0){
											$afscList = implode(",",$afscList);
										}
									}
									elseif($dataTypeSearch == "afsc uuid") {
										$afscUUID = true;
									}
                                    elseif($dataTypeSearch == "test uuid") {
										if ($testManager->loadTest($detailData['data'])) {
											$testUUID = true;
										}
										elseif ($testManager->loadIncompleteTest($detailData['data'])){
											$incompleteTestUUID = true;
										}
									}
							?>
							<tr>
								<td><?php echo $detailData['dataType']; ?></td>
								<?php if(isset($userName) && !empty($userName)): ?>
									<td>
										<a href="/admin/users/<?php echo $detailData['data']; ?>"><?php echo $userName; ?></a>
									</td>
								<?php elseif(isset($afscUUID) && ($afscUUID == true)): ?>
									<td>
										<?php echo $afsc->getAFSCName($detailData['data']); ?>
									</td>
								<?php elseif(isset($afscList) && !empty($afscList)): ?>
									<td>
										<?php echo $afscList; ?>
									</td>
                                <?php elseif(isset($testUUID) && ($testUUID == true)): ?>
                                    <td>
                                        <a href="/test/view/<?php echo $detailData['data']; ?>"><?php echo $detailData['data']; ?></a>
                                    </td>
								<?php elseif(isset($incompleteTestUUID) && ($incompleteTestUUID == true)): ?>
									<td>
										<a href="/admin/users/<?php echo $testManager->getIncompleteUserUUID(); ?>/tests/incomplete/view/<?php echo $detailData['data']; ?>"><?php echo $detailData['data']; ?></a>
									</td>
								<?php else: ?>
									<td><?php echo $detailData['data']; ?></td>
								<?php endif; ?>
							</tr>
								<?php
								$afscUUID = false;
								$afscList = false;
								$testUUID = false;
								$userName = false;
								$incompleteTestUUID = false;
								$dataTypeSearch = "";
								?>
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