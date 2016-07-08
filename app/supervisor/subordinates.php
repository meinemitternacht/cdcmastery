<?php
$userRole = $roleManager->verifyUserRole($_SESSION['userUUID']);

if(!$cdcMastery->verifySupervisor() && !$cdcMastery->verifyAdmin()){
	$systemMessages->addMessage("You are not authorized to use the Supervisor profile page.", "danger");
	$cdcMastery->redirect("/errors/403");
}

if(!empty($_POST) && isset($_POST['formAction'])){
	switch($_POST['formAction']){
		case "addSubordinate":
			$error = false;
			
			foreach($_POST['userUUID'] as $subordinateUUID):
				if($userRole == "trainingManager"):
					if(!$associationManager->addTrainingManagerAssociation($_SESSION['userUUID'], $subordinateUUID)):
						$error = true;
					endif;
				elseif($userRole == "supervisor"):
					if(!$associationManager->addSupervisorAssociation($_SESSION['userUUID'], $subordinateUUID)):
						$error = true;
					endif;
				endif;
			endforeach;
			
			if($error){
				$systemMessages->addMessage("There were errors encountered while associating subordinates with this user. Check the site log for details.", "danger");
			}
			else{
				$systemMessages->addMessage("Subordinate(s) associated successfully.", "success");
			}
		break;
		case "removeSubordinate":
			$error = false;
			
			foreach($_POST['userUUID'] as $subordinateUUID):
				if($userRole == "trainingManager"):
					if(!$associationManager->deleteTrainingManagerAssociation($_SESSION['userUUID'], $subordinateUUID)):
						$error = true;
					endif;
				elseif($userRole == "supervisor"):
					if(!$associationManager->deleteSupervisorAssociation($_SESSION['userUUID'], $subordinateUUID)):
						$error = true;
					endif;
				endif;
			endforeach;
			
			if($error){
				$systemMessages->addMessage("There were errors while removing subordinate association(s) for this user.  Check the site log for details.", "danger");
			}
			else{
				$systemMessages->addMessage("Subordinate association(s) removed successfully.", "success");
			}
		break;
	}
}

$userStatistics->setUserUUID($_SESSION['userUUID']);
$userInfo = new CDCMastery\UserManager($db, $systemLog, $emailQueue);
$userList = $userManager->listUsersByBase($userManager->getUserBase());

if($userRole == "trainingManager"):
	$subordinateList = $userManager->sortUserList($userStatistics->getTrainingManagerAssociations(), "userLastName");
	$subordinateCount = $userStatistics->getTrainingManagerSubordinateCount();
elseif($userRole == "supervisor"):
	$subordinateList = $userManager->sortUserList($userStatistics->getSupervisorAssociations(), "userLastName");
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
	<div class="row">
		<div class="4u">
			<section>
				<header>
					<h2><em><?php echo $userManager->getFullName(); ?></em></h2>
				</header>
			</section>
			<div class="sub-menu">
				<div class="menu-heading">
					Subordinate Associations
				</div>
				<ul>
					<li><a href="/supervisor/overview"><i class="icon-inline icon-20 ic-arrow-left"></i>Return to overview</a></li>
				</ul>
			</div>
            <div class="clearfix">&nbsp;</div>
			<p><strong>Subordinates: </strong><em><?php echo $subordinateCount; ?></em></p>
		</div>
		<div class="4u">
			<section>
				<header>
					<h2>Current Subordinates</h2>
				</header>
			</section>
			<?php if($subordinateList): ?>
			<form action="/supervisor/subordinates" method="POST">
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
			<form action="/supervisor/subordinates" method="POST">
				<input type="hidden" name="formAction" value="addSubordinate">
				Showing users from<br>
                <strong><?php echo $baseManager->getBaseName($userManager->getUserBase()); ?></strong><br>
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