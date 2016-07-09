<?php
$userAuthorization = new CDCMastery\UserAuthorizationQueueManager($db, $systemLog, $emailQueue);

if(!empty($_POST) && isset($_POST['formAction'])){
	switch($_POST['formAction']){
		case "authorizeUsers":
			$error = false;

			$authorizationQueue = $userAuthorization->listUserAuthorizeQueue();

			foreach($_POST['authorizeList'] as $authUUID){
				if($_POST['authReject'] == "authorize"){
					$userObj = new CDCMastery\UserManager($db, $systemLog, $emailQueue);

					if($userObj->loadUser($authorizationQueue[$authUUID]['userUUID'])){
						if($userObj->getUserRole() == $roleManager->getRoleUUIDByName("Supervisors") && $authorizationQueue[$authUUID]['roleUUID'] == $roleManager->getRoleUUIDByName("TrainingManagers")){
							/***
							 * Migrate Supervisor Subordinates to Training Manager if the user requested a role change themselves
							 */
							$objUserStatistics = new CDCMastery\UserStatisticsModule($db, $systemLog, $roleManager, $memcache);
							if($roleManager->getRoleType($userObj->getUserRole()) == "supervisor" && $roleManager->getRoleType($_POST['userRole']) == "trainingManager"){
								if($objUserStatistics->getSupervisorSubordinateCount() > 0){
									$subordinateList = $objUserStatistics->getSupervisorAssociations();

									if(count($subordinateList) > 1){
										foreach($subordinateList as $subordinateUUID){
											$associationManager->addTrainingManagerAssociation($userObj->getUUID(), $subordinateUUID);
											$associationManager->deleteSupervisorAssociation($userObj->getUUID(), $subordinateUUID);
										}
									}
									else{
										$associationManager->addTrainingManagerAssociation($userObj->getUUID(), $subordinateList[0]);
										$associationManager->deleteSupervisorAssociation($userObj->getUUID(), $subordinateList[0]);
									}

									if($objUserStatistics->getSupervisorSubordinateCount() > 0){
										$systemLog->setAction("ERROR_MIGRATE_SUBORDINATE_ASSOCIATIONS_ROLE_TYPE");
										$systemLog->setDetail("Source Role", $roleManager->getRoleName($userObj->getUserRole()));
										$systemLog->setDetail("Destination Role", $roleManager->getRoleName($_POST['userRole']));
										$systemLog->setDetail("User UUID", $userObj->getUUID());
										$systemLog->setDetail("Error", "After migration attempt, old associations still remained in the database.");
										$systemLog->saveEntry();

										$systemMessages->addMessage("After migration attempt, old associations still remained in the database. Contact CDCMastery Support for assistance with changing this user's role.", "danger");
									}
									else{
										$systemLog->setAction("MIGRATE_SUBORDINATE_ASSOCIATIONS_ROLE_TYPE");
										$systemLog->setDetail("User UUID", $userObj->getUUID());
										$systemLog->setDetail("Source Role", $roleManager->getRoleName($userObj->getUserRole()));
										$systemLog->setDetail("Destination Role", $roleManager->getRoleName($_POST['userRole']));
										$systemLog->saveEntry();
									}
								}
							}
						}

						if(!$userAuthorization->approveRoleAuthorization($authorizationQueue[$authUUID]['userUUID'],$authorizationQueue[$authUUID]['roleUUID'])){
							$error = true;
						}
					}
					else{
						$error = true;
					}
				}
				else{
					if(!$userAuthorization->rejectRoleAuthorization($authorizationQueue[$authUUID]['userUUID'],$authorizationQueue[$authUUID]['roleUUID'])){
						$error = true;
					}
				}
			}

			if($error){
				$systemMessages->addMessage("There were errors while processing roles for those user(s).  Check the site log for details.", "danger");
			}
			else{
				$systemMessages->addMessage("Processed user authorization(s) successfully.", "success");
			}
			break;
	}
}

$authorizationQueue = $userAuthorization->listUserAuthorizeQueue();

if($authorizationQueue): ?>
	<script type="text/javascript">
		$(document).ready(function() {
			$('#selectAll').click(function(event) {
				if(this.checked) {
					$('.selectUser').each(function() {
						this.checked = true;
					});
				}else{
					$('.selectUser').each(function() {
						this.checked = false;
					});
				}
			});

		});
	</script>
	<div class="container">
		<div class="row">
			<div class="5u">
				<section>
					<header>
						<h2>Authorize Roles for Users</h2>
					</header>
				</section>
			</div>
		</div>
		<div class="row">
			<div class="6u">
				<section>
					<p>Select the users you wish to authorize for roles below.</p>
					<form action="/admin/authorize-users" method="POST">
						<input type="hidden" name="formAction" value="authorizeUsers">
						<table>
							<tr>
								<th><input type="checkbox" name="selectAll" id="selectAll"></th>
								<th>User</th>
								<th>Role Requested</th>
								<th>Date Requested</th>
							</tr>
							<?php foreach($authorizationQueue as $authUUID => $authData): ?>
							<tr>
								<td><input type="checkbox" class="selectUser" name="authorizeList[]" value="<?php echo $authUUID; ?>"></td>
								<td><a href="/admin/profile/<?php echo $authData['userUUID']; ?>"><?php echo $userManager->getUserNameByUUID($authData['userUUID']); ?></a></td>
								<td><?php echo $roleManager->getRoleName($authData['roleUUID']); ?></td>
								<td><?php echo $cdcMastery->outputDateTime($authData['dateRequested'],$_SESSION['timeZone']); ?></td>
							</tr>
							<?php endforeach; ?>
						</table>
						<div class="clearfix">&nbsp;</div>
						<label for="authReject">Choose whether to authorize or reject the selected users.</label><br>
						<input type="radio" name="authReject" value="authorize" checked="CHECKED"> Authorize<br>
						<input type="radio" name="authReject" value="reject"> Reject
						<div class="clearfix">&nbsp;</div>
						<input type="submit" value="Authorize Users">
					</form>
				</section>
			</div>
		</div>
	</div>
<?php else:
	$systemMessages->addMessage("There are no users awaiting authorization.", "info");
	$cdcMastery->redirect("/admin");
endif;