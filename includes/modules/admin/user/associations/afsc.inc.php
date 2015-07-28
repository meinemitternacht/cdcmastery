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
                $_SESSION['messages'][] = "There were errors while adding AFSC association(s) for this user.  Check the site log for details.";
			}
			else{
                $_SESSION['messages'][] = "AFSC association(s) added successfully.";
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
                $_SESSION['messages'][] = "There were errors while removing AFSC association(s) for this user.  Check the site log for details.";
			}
			else{
                $_SESSION['messages'][] = "AFSC association(s) removed successfully.";
			}
		break;
		case "removePendingAssociation":
			$error = false;
			
			foreach($_POST['afscUUID'] as $afscUUID){
				if(!$assoc->deletePendingAFSCAssociation($userUUID, $afscUUID)){
					$error = true;
				}
			}
			
			if($error){
                $_SESSION['messages'][] = "There were errors while removing pending AFSC association(s) for this user.  Check the site log for details.";
			}
			else{
				$_SESSION['messages'][] = "Pending AFSC association(s) removed successfully.";
			}
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
				<div class="sub-menu">
					<div class="menu-heading">
						AFSC Associations
					</div>
					<ul>
						<li><a href="/admin/users/<?php echo $userUUID; ?>"><i class="fa fa-caret-square-o-left fa-fw"></i>Return to user manager</a></li>
					</ul>
				</div>
			</section>
		</div>
		<div class="4u">
			<section>
				<header>
					<h2>Current Associations</h2>
				</header>
			</section>
			<?php if($userAFSCList): ?>
			<ul>
				<?php foreach($userAFSCList as $afscUUID => $afscName): ?>
				<li>&raquo; <?php echo $afscName; ?></li>
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
				<li>&raquo; <?php echo $afsc->getAFSCName($pendingAFSCUUID); ?></li>
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
				if(!empty($userAFSCList)) {
                    foreach ($userAFSCList as $userAFSC) {
                        if (isset($afscList[$userAFSC])) {
                            unset($afscList[$userAFSC]);
                        }
                    }
                }
				
				if($afscList): ?>
				<select class="form-object-full" name="afscUUID[]" size="8" MULTIPLE>
					<?php foreach($afscList as $listUUID => $listDetails): ?>
					<option value="<?php echo $listUUID; ?>"><?php echo $listDetails['afscName']; if($listDetails['afscFOUO']){ echo "*"; } ?></option>
					<?php endforeach; ?>
				</select>
				<br>
				<input type="submit" value="Add">
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
					<?php foreach($userAFSCList as $listUUID => $listAFSCName): ?>
					<option value="<?php echo $listUUID; ?>"><?php echo $listAFSCName; ?></option>
					<?php endforeach; ?>
				</select>
				<br>
				<input type="submit" value="Remove">
				<?php else: ?>
				<em>This user has no AFSC associations.</em>
				<?php endif; ?>
			</form>
		</div>
		<div class="4u">
			<section>
				<header>
					<h2>Approve Pending Associations</h2>
				</header>
			</section>
			<form action="/admin/users/<?php echo $userUUID; ?>/associations/afsc" method="POST">
				<input type="hidden" name="formAction" value="approvePendingAssociation">
				<?php if($userPendingAFSCList): ?>
				<select class="form-object-full" name="afscUUID[]" size="8" MULTIPLE>
					<?php foreach($userPendingAFSCList as $listUUID => $listAFSCName): ?>
					<option value="<?php echo $listUUID; ?>"><?php echo $listAFSCName; ?></option>
					<?php endforeach; ?>
				</select>
				<br>
				<input type="submit" value="Approve">
				<?php else: ?>
				<em>This user has no pending AFSC associations.</em>
				<?php endif; ?>
			</form>
		</div>
	</div>
	<div class="row">
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
					<?php foreach($userPendingAFSCList as $listUUID => $listAFSCName): ?>
					<option value="<?php echo $listUUID; ?>"><?php echo $listAFSCName; ?></option>
					<?php endforeach; ?>
				</select>
				<br>
				<input type="submit" value="Remove">
				<?php else: ?>
				<em>This user has no pending AFSC associations.</em>
				<?php endif; ?>
			</form>
		</div>
	</div>
</div>