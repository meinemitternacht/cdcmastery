<?php
$userActivation = new userActivation($db,$log,$emailQueue);

if(!empty($_POST) && isset($_POST['formAction'])){
	switch($_POST['formAction']){
		case "activateUsers":
			$error = false;

			foreach($_POST['activationCodeList'] as $activationCode){
				if(!$userActivation->activateUser($activationCode)){
					$error = true;
				}
			}

			if($error){
				$sysMsg->addMessage("There were errors while activating those user(s).  Check the site log for details.");
			}
			else{
				$sysMsg->addMessage("User(s) activated successfully.");
			}
			break;
	}
}

$unactivatedUsersList = $userActivation->listUnactivatedUsers();

if($unactivatedUsersList): ?>
	<script type="text/javascript">
		$(document).ready(function() {
			$('#selectAll').click(function(event) {  //on click
				if(this.checked) { // check select status
					$('.selectUser').each(function() { //loop through each checkbox
						this.checked = true;  //select all checkboxes with class "checkbox1"
					});
				}else{
					$('.selectUser').each(function() { //loop through each checkbox
						this.checked = false; //deselect all checkboxes with class "checkbox1"
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
			<div class="6u">
				<section>
					<p>Select the users you wish to activate below.  Please note that it is preferable for the user to activate with the code they received in their e-mail.</p>
					<form action="/admin/activate-users" method="POST">
						<input type="hidden" name="formAction" value="activateUsers">
						<table>
							<tr>
								<th><input type="checkbox" name="selectAll" id="selectAll"></th>
								<th>User</th>
								<th>Activation Code Expiration</th>
							</tr>
							<?php foreach($unactivatedUsersList as $activationCode => $activationData): ?>
							<tr>
								<td><input type="checkbox" class="selectUser" name="activationCodeList[]" value="<?php echo $activationCode; ?>"></td>
								<td><a href="/admin/profile/<?php echo $activationData['userUUID']; ?>"><?php echo $user->getUserNameByUUID($activationData['userUUID']); ?></a></td>
								<td><?php echo $cdcMastery->outputDateTime($activationData['timeExpires'],$_SESSION['timeZone']); ?></td>
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
	$sysMsg->addMessage("There are no unactivated users.");
	$cdcMastery->redirect("/admin");
endif;