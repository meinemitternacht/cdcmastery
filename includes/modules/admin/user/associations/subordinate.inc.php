<?php
$userRole = $roles->verifyUserRole($userUUID);

if(!empty($_POST) && isset($_POST['formAction'])){
	switch($_POST['formAction']){
		case "addSubordinate":
			$error = false;
			
			foreach($_POST['userUUID'] as $subordinateUUID):
				if($userRole == "trainingManager"):
					if(!$assoc->addTrainingManagerAssociation($userUUID, $subordinateUUID)):
						$error = true;
					endif;
				elseif($userRole == "supervisor"):
					if(!$assoc->addSupervisorAssociation($userUUID, $subordinateUUID)):
						$error = true;
					endif;
				endif;
			endforeach;
			
			if($error){
				$messages[] = "There were errors encountered while associating subordinates with this user. Check the site log for details.";
			}
			else{
				$messages[] = "Subordinate(s) associated successfully.";
			}
		break;
		case "removeSubordinate":
			$error = false;
			
			foreach($_POST['userUUID'] as $subordinateUUID):
				if($userRole == "trainingManager"):
					if(!$assoc->deleteTrainingManagerAssociation($userUUID, $subordinateUUID)):
						$error = true;
					endif;
				elseif($userRole == "supervisor"):
					if(!$assoc->deleteSupervisorAssociation($userUUID, $subordinateUUID)):
						$error = true;
					endif;
				endif;
			endforeach;
			
			if($error){
				$messages[] = "There were errors while removing subordinate association(s) for this user.  Check the site log for details.";
			}
			else{
				$messages[] = "Subordinate association(s) removed successfully.";
			}
		break;
	}
}

$userStatistics->setUserUUID($userUUID);
$userInfo = new user($db, $log, $emailQueue);
$userList = $user->listUsers();

if($userRole == "trainingManager"):
	$subordinateList = $user->sortUserList($userStatistics->getTrainingManagerAssociations(),"userLastName");
	$subordinateCount = $userStatistics->getTrainingManagerSubordinateCount();
elseif($userRole == "supervisor"):
	$subordinateList = $user->sortUserList($userStatistics->getSupervisorAssociations(),"userLastName");
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
					<?php foreach($subordinateList as $subordinateKey => $subordinate):
							if(isset($userList[$subordinateKey]))
								unset($userList[$subordinateKey]); ?>
							<li><input class="subordinateCheckbox" type="checkbox" name="userUUID[]" value="<?php echo $subordinateKey; ?>"> <?php echo $subordinate['fullName']; ?></li>
					<?php endforeach; ?>
					<li><input type="submit" value="Remove Subordinate(s)"></li>
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
				Select users<br>
				<select name="userUUID[]" size="15" MULTIPLE>
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