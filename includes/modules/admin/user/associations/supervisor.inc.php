<?php
if(!empty($_POST) && isset($_POST['formAction'])){
	switch($_POST['formAction']){
		case "addSupervisor":
			$error = false;
				
			foreach($_POST['userUUID'] as $supervisorUUID):
				if(!$assoc->addSupervisorAssociation($supervisorUUID,$userUUID)):
					$error = true;
				endif;
			endforeach;
				
			if($error){
				$messages[] = "There were errors encountered while associating supervisors with this user. Check the site log for details.";
			}
			else{
				$messages[] = "Supervisor(s) associated successfully.";
			}
			break;
		case "removeSupervisor":
			$error = false;
				
			foreach($_POST['userUUID'] as $supervisorUUID):
				if(!$assoc->deleteSupervisorAssociation($supervisorUUID,$userUUID)):
					$error = true;
				endif;
			endforeach;
				
			if($error){
				$messages[] = "There were errors while removing supervisor association(s) for this user.  Check the site log for details.";
			}
			else{
				$messages[] = "Supervisor association(s) removed successfully.";
			}
			break;
	}
}

$userStatistics->setUserUUID($userUUID);
$assocSupervisorList = $user->sortUserList($userStatistics->getUserSupervisors(),"userLastName");
$supervisorList = $user->sortUserList($roles->listSupervisors(),"userLastName");
$supervisorCount = $userStatistics->getUserSupervisorCount();
?>
<script type="text/javascript">
$(document).ready(function() {
	$('#selectAll').click(function(event) {
		if(this.checked) {
			$('.supervisorCheckbox').each(function() {
				this.checked = true;
			});
		}else{
			$('.supervisorCheckbox').each(function() {
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
					Supervisor Associations
				</div>
				<ul>
					<li><a href="/admin/users/<?php echo $userUUID; ?>"><i class="icon-inline icon-20 ic-arrow-left"></i>Return to user manager</a></li>
				</ul>
			</div>
			<br>
			<p><strong>Supervisors: </strong><em><?php echo $supervisorCount; ?></em></p>
		</div>
		<div class="4u">
			<section>
				<header>
					<h2>Current Supervisors</h2>
				</header>
			</section>
			<?php if($assocSupervisorList): ?>
			<form action="/admin/users/<?php echo $userUUID; ?>/associations/supervisor" method="POST">
				<input type="hidden" name="formAction" value="removeSupervisor">
				<ul>
					<li><input type="checkbox" id="selectAll"> <em>Select All</em>
					<?php foreach($assocSupervisorList as $supervisorKey => $supervisor):
							if(isset($userList[$supervisorKey]))
								unset($userList[$supervisorKey]); ?>
							<li><input class="supervisorCheckbox" type="checkbox" name="userUUID[]" value="<?php echo $supervisorKey; ?>"> <?php echo $supervisor['fullName']; ?></li>
					<?php endforeach; ?>
					<li><input type="submit" value="Remove Supervisor(s)"></li>
				</ul>
			</form>
			<?php else: ?>
			<em>This user has no supervisors.</em>
			<?php endif; ?>
		</div>
		<div class="4u">
			<section>
				<header>
					<h2>Add Supervisor</h2>
				</header>
			</section>
			<form action="/admin/users/<?php echo $userUUID; ?>/associations/supervisor" method="POST">
				<input type="hidden" name="formAction" value="addSupervisor">
				Select supervisor(s)<br>
				<select name="userUUID[]" size="15" MULTIPLE>
					<?php foreach($supervisorList as $supervisorUUID => $supervisor): ?>
						<option value="<?php echo $supervisorUUID; ?>"><?php echo $supervisor['userLastName']; ?>, <?php echo $supervisor['userFirstName']; ?> <?php echo $supervisor['userRank']; ?></option>
					<?php endforeach; ?>
				</select>
				<br>
				<input type="submit" value="Add">
			</form>
		</div>
	</div>
</div>