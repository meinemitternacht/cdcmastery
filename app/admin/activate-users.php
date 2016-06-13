<?php
/**
 * To discourage activating accounts that entered an invalid e-mail address, limit this functionality to administrators
 */
if(!$cdcMastery->verifyAdmin()){
    $sysMsg->addMessage("Only site administrators can manually activate users.","info");
    $cdcMastery->redirect("/errors/403");
}

$userActivation = new userActivation($db,$log,$emailQueue);

if(!empty($_POST) && isset($_POST['formAction'])){
	switch($_POST['formAction']){
		case "activateUsers":
			$error = false;

			if(empty($_POST['activationCodeList']) || !isset($_POST['activationCodeList'])){
				$sysMsg->addMessage("You must select users to activate.","warning");
			}
			else{
				foreach($_POST['activationCodeList'] as $activationCode){
					if(!$userActivation->activateUser($activationCode,$_SESSION['userUUID'])){
						$error = true;
					}
				}

				if($error){
					$sysMsg->addMessage("There were errors while activating those user(s).  Check the site log for details.","danger");
				}
				else{
					$sysMsg->addMessage("User(s) activated successfully.","success");
				}
			}
			break;
	}
}

$unactivatedUsersList = $userActivation->listUnactivatedUsers();

if($unactivatedUsersList): ?>
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
						<h2>Activate Users</h2>
					</header>
				</section>
			</div>
		</div>
		<div class="row">
			<div class="12u">
				<section>
					<p>Select the users you wish to activate below.  Please note that it is preferable for the user to
                       activate with the code they received in their e-mail. Unactivated accounts will be removed after
                       30 days, and a reminder e-mail is sent after three days informing users of this policy.</p>
					<form action="/admin/activate-users" method="POST">
						<input type="hidden" name="formAction" value="activateUsers">
						<table>
							<tr>
								<th><input type="checkbox" name="selectAll" id="selectAll"></th>
								<th>User</th>
                                <th>Date Registered</th>
                                <th>E-mail Address</th>
								<th>Code Expires (UTC)</th>
                                <th>Reminder Sent</th>
							</tr>
							<?php foreach($unactivatedUsersList as $activationCode => $activationData): ?>
                            <?php $actUserObj = new user($db,$log,$emailQueue); ?>
                            <?php $actUserObj->loadUser($activationData['userUUID']); ?>
							<tr>
								<td><input type="checkbox" class="selectUser" name="activationCodeList[]" value="<?php echo $activationCode; ?>"></td>
								<td><a href="/admin/profile/<?php echo $activationData['userUUID']; ?>"><?php echo $actUserObj->getFullName(); ?></a></td>
                                <td><?php echo $cdcMastery->formatDateTime($actUserObj->getUserDateRegistered()); ?></td>
                                <td><?php echo $actUserObj->getUserEmail(); ?></td>
								<td>
                                    <?php if(strtotime($activationData['timeExpires']) <= time()): ?>
                                        <span style="color:red"><?php echo $cdcMastery->formatDateTime($activationData['timeExpires']); ?></span>
                                    <?php else: ?>
									    <?php echo $cdcMastery->formatDateTime($activationData['timeExpires']); ?>
                                    <?php endif; ?>
								</td>
                                <td>
                                    <?php if($actUserObj->getReminderSent()) { echo "Yes"; } else { echo "No"; } ?>
                                </td>
							</tr>
							<?php endforeach; ?>
						</table>
						<div class="clearfix">&nbsp;</div>
						<input type="submit" value="Activate Users">
					</form>
				</section>
			</div>
		</div>
	</div>
<?php else:
	$sysMsg->addMessage("There are no unactivated users.","info");
	$cdcMastery->redirect("/admin");
endif;