<?php
if(!empty($_POST) && isset($_POST['formAction'])){
	switch($_POST['formAction']){
		case "addAssociation":
			$error = false;
			
			foreach($_POST['afscUUID'] as $afscUUID){
				if(!$assoc->addAFSCAssociation($userUUID, $afscUUID)){
					$error = true;
				}
			}
			
			if($error){
				$messages[] = "There were errors while adding AFSC association(s) for this user.  Check the site log for details.";
			}
			else{
				$messages[] = "AFSC association(s) added successfully.";
			}
		break;
		case "removeAssociation":
			$error = false;
			
			foreach($_POST['afscUUID'] as $afscUUID){
				if(!$assoc->deleteAFSCAssociation($userUUID, $afscUUID)){
					$error = true;
				}
			}
			
			if($error){
				$messages[] = "There were errors while removing AFSC association(s) for this user.  Check the site log for details.";
			}
			else{
				$messages[] = "AFSC association(s) removed successfully.";
			}
		break;
	}
}

$userStatistics->setUserUUID($userUUID);
$userInfo = new user($db, $log);
$userList = $user->listUsers();

if($roles->verifyUserRole($userUUID) == "trainingManager"):
	$subordinateList = $userStatistics->getTrainingManagerAssociations();
	$subordinateCount = $userStatistics->getTrainingManagerSubordinateCount();
elseif($roles->verifyUserRole($userUUID) == "supervisor"):
	$subordinateList = $userStatistics->getSupervisorAssociations();
	$subordinateCount = $userStatistics->getSupervisorSubordinateCount();
else:
	$cdcMastery->redirect("/admin/users/".$userUUID);
endif;
?>
<script type="text/javascript">
$(document).ready(function() {
	$('#selectAll').click(function(event) {
		if(this.checked) {
			$('.subordinateCheckbox').each(function() {
				this.checked = true;
			});
		}else{
			$('.subordinateCheckbox').each(function() {
				this.checked = false;
			});
		}
	});
});
</script>
<div class="container">
	<?php if(isset($messages)): ?>
	<div class="systemMessages">
		<ul>
		<?php foreach($messages as $message): ?>
			<li><?php echo $message; ?></li>
		<?php endforeach; ?>
		</ul>
	</div>
	<?php endif; ?>
	<div class="row">
		<div class="4u">
			<section>
				<header>
					<h2><em><?php echo $objUser->getFullName(); ?></em></h2>
				</header>
			</section>
			<div class="sub-menu">
				<div class="menu-heading">
					Subordinate Associations
				</div>
				<ul>
					<li><a href="/admin/users/<?php echo $userUUID; ?>"><i class="fa fa-caret-square-o-left"></i>Return to user manager</a></li>
				</ul>
			</div>
			<br>
			<p><strong>Subordinates: </strong><em><?php echo $subordinateCount; ?></em></p>
		</div>
		<div class="4u">
			<section>
				<header>
					<h2>Current Subordinates</h2>
				</header>
			</section>
			<?php if($subordinateList): ?>
			<form action="/admin/users/<?php echo $userUUID; ?>/associations/subordinate" method="POST">
				<input type="hidden" name="formAction" value="removeSubordinate">
				<ul>
					<li><input type="checkbox" id="selectAll"> <em>Select All</em>
					<?php foreach($subordinateList as $subordinate):
							if(isset($userList[$subordinate])){
								unset($userList[$subordinate]);
							} ?>
						<?php if($userInfo->loadUser($subordinate)): ?>
							<li><input class="subordinateCheckbox" type="checkbox" name="userUUID[]" value="<?php echo $subordinate; ?>"> <?php echo $userInfo->getFullName(); ?></li>
						<?php else: ?>
							<li>Unknown</li>
						<?php endif; ?>
					<?php endforeach; ?>
				</ul>
			</form>
			<?php else: ?>
			<em>This user has no subordinates.</em>
			<?php endif; ?>
		</div>
		<div class="4u">
			<section>
				<header>
					<h2>Add Subordinate</h2>
				</header>
			</section>
			<form action="/admin/users/<?php echo $userUUID; ?>/associations/subordinate" method="POST">
				<input type="hidden" name="formAction" value="addSubordinate">
				Select user:<br>
				<select name="userList[]" id="userList">
					<?php foreach($userList as $userListUUID => $userListData): ?>
						<option value="<?php echo $userListUUID; ?>"><?php echo $userListData['userLastName']; ?>, <?php echo $userListData['userFirstName']; ?> <?php echo $userListData['userRank']; ?></option>
					<?php endforeach; ?>
				</select>
				<br>
				<input type="submit" value="Add">
			</form>
		</div>
	</div>
</div>