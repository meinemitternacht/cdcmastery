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
				$sysMsg->addMessage("There were errors encountered while associating subordinates with this user. Check the site log for details.","danger");
			}
			else{
				$sysMsg->addMessage("Subordinate(s) associated successfully.","success");
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
				$sysMsg->addMessage("There were errors while removing subordinate association(s) for this user.  Check the site log for details.","danger");
			}
			else{
				$sysMsg->addMessage("Subordinate association(s) removed successfully.","success");
			}
		break;
	}
}

$userStatistics->setUserUUID($userUUID);
$userList = $user->listUsersByBase($user->getUserBase());

$subordinateList = false;
$subordinateCount = 0;

if($userRole == "trainingManager"):
	$rawList = $userStatistics->getTrainingManagerAssociations();
	if(is_array($rawList)):
		$subordinateList = $user->sortUserList($rawList,"userLastName");
		$subordinateCount = $userStatistics->getTrainingManagerSubordinateCount();
	endif;
elseif($userRole == "supervisor"):
	$rawList = $userStatistics->getSupervisorAssociations();
	if(is_array($rawList)):
		$subordinateList = $user->sortUserList($rawList,"userLastName");
		$subordinateCount = $userStatistics->getSupervisorSubordinateCount();
	endif;
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
					<li><a href="/admin/users/<?php echo $userUUID; ?>"><i class="icon-inline icon-20 ic-arrow-left"></i>Return to user manager</a></li>
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
					<?php foreach($subordinateList as $subordinateKey => $subordinate): ?>
							<?php if(isset($userList[$subordinateKey])) unset($userList[$subordinateKey]); ?>
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
				<em>Showing users from <br><?php echo $bases->getBaseName($user->getUserBase()); ?></em>
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