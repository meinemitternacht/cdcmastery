<?php
$userAuthorization = new userAuthorizationQueue($db,$log,$emailQueue);

if(!empty($_POST) && isset($_POST['formAction'])){
	switch($_POST['formAction']){
		case "authorizeUsers":
			$error = false;

			$authorizationQueue = $userAuthorization->listUserAuthorizeQueue();

			foreach($_POST['authorizeList'] as $authUUID){
				if($_POST['authReject'] == "authorize"){
					if(!$userAuthorization->approveRoleAuthorization($authorizationQueue[$authUUID]['userUUID'],$authorizationQueue[$authUUID]['roleUUID'])){
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
				$sysMsg->addMessage("There were errors while processing roles for those user(s).  Check the site log for details.");
			}
			else{
				$sysMsg->addMessage("User(s) authorized successfully.");
			}
			break;
	}
}

$authorizationQueue = $userAuthorization->listUserAuthorizeQueue();

if($authorizationQueue): ?>
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
								<td><a href="/admin/profile/<?php echo $authData['userUUID']; ?>"><?php echo $user->getUserNameByUUID($authData['userUUID']); ?></a></td>
								<td><?php echo $roles->getRoleName($authData['roleUUID']); ?></td>
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
	$sysMsg->addMessage("There are no users awaiting authorization.");
	$cdcMastery->redirect("/admin");
endif;