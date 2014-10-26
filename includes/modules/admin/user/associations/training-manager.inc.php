<?php
if(!empty($_POST) && isset($_POST['formAction'])){
	switch($_POST['formAction']){
		case "addTrainingManager":
			$error = false;
				
			foreach($_POST['userUUID'] as $trainingManagerUUID):
				if(!$assoc->addTrainingManagerAssociation($trainingManagerUUID,$userUUID)):
					$error = true;
				endif;
			endforeach;
				
			if($error){
				$messages[] = "There were errors encountered while associating training managers with this user. Check the site log for details.";
			}
			else{
				$messages[] = "Training manager(s) associated successfully.";
			}
			break;
		case "removeTrainingManager":
			$error = false;
				
			foreach($_POST['userUUID'] as $trainingManagerUUID):
				if(!$assoc->deleteTrainingManagerAssociation($trainingManagerUUID,$userUUID)):
					$error = true;
				endif;
			endforeach;
				
			if($error){
				$messages[] = "There were errors while removing training manager association(s) for this user.  Check the site log for details.";
			}
			else{
				$messages[] = "Training manager association(s) removed successfully.";
			}
			break;
	}
}

$userStatistics->setUserUUID($userUUID);
$assocTrainingManagerList = $user->sortUserList($userStatistics->getUserTrainingManagers(),"userLastName");
$trainingManagerList = $user->sortUserList($roles->listTrainingManagers(),"userLastName");
$trainingManagerCount = $userStatistics->getUserTrainingManagerCount();
?>
<script type="text/javascript">
$(document).ready(function() {
	$('#selectAll').click(function(event) {
		if(this.checked) {
			$('.trainingManagerCheckbox').each(function() {
				this.checked = true;
			});
		}else{
			$('.trainingManagerCheckbox').each(function() {
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
					Training Manager Associations
				</div>
				<ul>
					<li><a href="/admin/users/<?php echo $userUUID; ?>"><i class="fa fa-caret-square-o-left"></i>Return to user manager</a></li>
				</ul>
			</div>
			<br>
			<p><strong>Training Managers: </strong><em><?php echo $trainingManagerCount; ?></em></p>
		</div>
		<div class="4u">
			<section>
				<header>
					<h2>Current Training Managers</h2>
				</header>
			</section>
			<?php if($assocTrainingManagerList): ?>
			<form action="/admin/users/<?php echo $userUUID; ?>/associations/training-manager" method="POST">
				<input type="hidden" name="formAction" value="removeTrainingManager">
				<ul>
					<li><input type="checkbox" id="selectAll"> <em>Select All</em>
					<?php foreach($assocTrainingManagerList as $trainingManagerKey => $trainingManager):
							if(isset($userList[$trainingManagerKey]))
								unset($userList[$trainingManagerKey]); ?>
							<li><input class="trainingManagerCheckbox" type="checkbox" name="userUUID[]" value="<?php echo $trainingManagerKey; ?>"> <?php echo $trainingManager['fullName']; ?></li>
					<?php endforeach; ?>
					<li><input type="submit" value="Remove Training Manager(s)"></li>
				</ul>
			</form>
			<?php else: ?>
			<em>This user has no training managers.</em>
			<?php endif; ?>
		</div>
		<div class="4u">
			<section>
				<header>
					<h2>Add Training Manager</h2>
				</header>
			</section>
			<form action="/admin/users/<?php echo $userUUID; ?>/associations/training-manager" method="POST">
				<input type="hidden" name="formAction" value="addTrainingManager">
				Select Training Manager(s)<br>
				<select name="userUUID[]" size="15" MULTIPLE>
					<?php foreach($trainingManagerList as $trainingManagerUUID => $trainingManager): ?>
						<option value="<?php echo $trainingManagerUUID; ?>"><?php echo $trainingManager['userLastName']; ?>, <?php echo $trainingManager['userFirstName']; ?> <?php echo $trainingManager['userRank']; ?></option>
					<?php endforeach; ?>
				</select>
				<br>
				<input type="submit" value="Add">
			</form>
		</div>
	</div>
</div>