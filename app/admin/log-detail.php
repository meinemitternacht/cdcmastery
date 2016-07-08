<?php
$testManager = new CDCMastery\TestManager($db, $systemLog, $afscManager);

$logUUID = isset($_SESSION['vars'][0]) ? $_SESSION['vars'][0] : false;
$returnPath = isset($_SESSION['vars'][1]) ? strtolower($_SESSION['vars'][1]) : "log";

if($logUUID):
	if($systemLog->verifyLogUUID($logUUID)): 
		$logData = new CDCMastery\SystemLog($db);
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
				<div class="8u">
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
								<td><a href="/admin/profile/<?php echo $logData->getUserUUID(); ?>" title="View Profile"><?php echo $userManager->getUserByUUID($logData->getUserUUID()); ?></a></td>
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
							<?php if(!empty($logData->getUserAgent())): ?>
							<tr>
								<th>User Agent</th>
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
                                if($cdcMastery->is_serialized($detailData['data'])): ?>
                                    <tr>
                                        <td><?php echo $detailData['dataType']; ?></td>
                                        <td>
                                    <?php
                                    $data = unserialize($detailData['data']);
                                    if(is_array($data)){
                                        $dataCount = count($data);
                                        $i = 1;
                                        foreach($data as $dataVal){
                                            $linkStr = $systemLog->formatDetailData($dataVal);

                                            if($i < $dataCount){
                                                echo $linkStr . ", " . PHP_EOL;
                                            }
                                            else{
                                                echo $linkStr . PHP_EOL;
                                            }
                                            $i++;
                                        }
                                    }
                                    ?>
                                        </td>
                                    </tr>
                                    <?php
                                else:
                                    $linkStr = $systemLog->formatDetailData($detailData['data']);
                                    ?>
                                    <tr>
                                        <td><?php echo $detailData['dataType']; ?></td>
                                        <td><?php echo $linkStr; ?></td>
                                    </tr>
                                    <?php
                                endif;
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