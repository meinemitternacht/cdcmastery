<?php
if(!empty($_POST) && isset($_POST['formAction'])){
	switch($_POST['formAction']){
		case "addAssociation":
			$message[] = "Would have added: ".implode(",",$_POST['afscUUID']);
		break;
		case "removeAssociation":
			$message[] = "Would have removed: ".implode(",",$_POST['afscUUID']);
		break;
		case "removePendingAssociation":
			$message[] = "Would have removed: ".implode(",",$_POST['afscUUID']);
		break;
	}
}

$userStatistics->setUserUUID($userUUID);
$userAFSCList = $userStatistics->getAFSCAssociations();
$userPendingAFSCList = $userStatistics->getPendingAFSCAssociations();
?>
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
					AFSC Associations
				</div>
				<ul>
					<li><a href="/admin/users/<?php echo $userUUID; ?>"><i class="fa fa-caret-square-o-left"></i>Return to user manager</a></li>
				</ul>
			</div>
		</div>
		<div class="4u">
			<section>
				<header>
					<h2>Current Associations</h2>
				</header>
			</section>
			<?php if($userAFSCList): ?>
			<ul>
				<?php foreach($userAFSCList as $afscUUID): ?>
				<li>&raquo;<?php echo $afsc->getAFSCName($afscUUID); ?></li>
				<?php endforeach; ?>
			</ul>
			<?php else: ?>
			<em>This user has no AFSC associations.</em>
			<?php endif; ?>
		</div>
		<div class="4u">
			<section>
				<header>
					<h2>Pending Associations</h2>
				</header>
			</section>
			<?php if($userPendingAFSCList): ?>
			<ul>
				<?php foreach($userPendingAFSCList as $pendingAFSCUUID): ?>
				<li><?php echo $afsc->getAFSCName($pendingAFSCUUID); ?></li>
				<?php endforeach; ?>
			</ul>
			<?php else: ?>
			<em>This user has no pending AFSC associations.</em>
			<?php endif; ?>
		</div>
	</div>
	<div class="row">
		<div class="4u">
			<section>
				<header>
					<h2>Add Association</h2>
				</header>
			</section>
			<form action="/admin/users/<?php echo $userUUID; ?>/associations/afsc" method="POST">
				<input type="hidden" name="formAction" value="addAssociation">
				<?php
				$afscList = $afsc->listAFSC();
				
				if($afscList): ?>
				<select class="form-object-full" name="afscUUID[]" size="8" MULTIPLE>
					<?php foreach($afscList as $listUUID => $listDetails): ?>
					<option value="<?php echo $listUUID; ?>"><?php echo $listDetails['afscName']; if($listDetails['afscFOUO']){ echo "*"; } ?></option>
					<?php endforeach; ?>
				</select>
				<br>
				<input type="submit" value="Add Association">
				<p><em>An asterisk (*) denotes a FOUO AFSC</em></p>
				<?php else: ?>
				<em>There are no AFSCs in the database.</em>
				<?php endif; ?>
			</form>
		</div>
		<div class="4u">
			<section>
				<header>
					<h2>Remove Associations</h2>
				</header>
			</section>
			<form action="/admin/users/<?php echo $userUUID; ?>/associations/afsc" method="POST">
				<input type="hidden" name="formAction" value="removeAssociation">
				<?php if($userAFSCList): ?>
				<select class="form-object-full" name="afscUUID[]" size="8" MULTIPLE>
					<?php foreach($userAFSCList as $listUUID): ?>
					<option value="<?php echo $listUUID; ?>"><?php echo $afsc->getAFSCName($listUUID); ?></option>
					<?php endforeach; ?>
				</select>
				<br>
				<input type="submit" value="Remove Association">
				<?php else: ?>
				<em>This user has no AFSC associations.</em>
				<?php endif; ?>
			</form>
		</div>
		<div class="4u">
			<section>
				<header>
					<h2>Remove Pending Associations</h2>
				</header>
			</section>
			<form action="/admin/users/<?php echo $userUUID; ?>/associations/afsc" method="POST">
				<input type="hidden" name="formAction" value="removePendingAssociation">
				<?php if($userPendingAFSCList): ?>
				<select class="form-object-full" name="afscUUID[]" size="8" MULTIPLE>
					<?php foreach($userPendingAFSCList as $listUUID): ?>
					<option value="<?php echo $listUUID; ?>"><?php echo $afsc->getAFSCName($listUUID); ?></option>
					<?php endforeach; ?>
				</select>
				<br>
				<input type="submit" value="Remove Association">
				<?php else: ?>
				<em>This user has no pending AFSC associations.</em>
				<?php endif; ?>
			</form>
		</div>
	</div>
</div>