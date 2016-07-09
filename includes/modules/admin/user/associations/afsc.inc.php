<?php
if(!empty($_POST) && isset($_POST['formAction'])){
	switch($_POST['formAction']) {
		case "addAssociation":
			$error = false;

			if (isset($_POST['afscUUID'])) {
				foreach ($_POST['afscUUID'] as $afscUUID) {
					if (!$associationManager->addAFSCAssociation($userUUID, $afscUUID)) {
						$error = true;
					}
				}

				if ($error) {
					$systemMessages->addMessage("There were errors while adding AFSC association(s) for this user.  Check the site log for details.", "danger");
				}
				else {
					$systemMessages->addMessage("AFSC association(s) added successfully.", "success");
				}
			}
			else{
				$error = true;
				$systemMessages->addMessage("You did not provide any AFSC's to associate.", "warning");
			}
			break;
		case "removeAssociation":
			$error = false;

			if (isset($_POST['afscUUID'])) {
				foreach ($_POST['afscUUID'] as $afscUUID) {
					if (!$associationManager->deleteAFSCAssociation($userUUID, $afscUUID)) {
						$error = true;
					}
				}

				if ($error) {
					$systemMessages->addMessage("There were errors while removing AFSC association(s) for this user.  Check the site log for details.", "danger");
				}
				else {
					$systemMessages->addMessage("AFSC association(s) removed successfully.", "success");
				}
			}
			else{
				$error = true;
				$systemMessages->addMessage("You did not provide any AFSC's to unassociate.", "warning");
			}
			break;
		case "approvePendingAssociation":
			$error = false;

			if (isset($_POST['afscUUID'])) {
				foreach ($_POST['afscUUID'] as $afscUUID) {
					if (!$associationManager->approvePendingAFSCAssociation($userUUID, $afscUUID)) {
						$error = true;
					}
				}

				if ($error) {
					$systemMessages->addMessage("There were errors while approving pending AFSC association(s) for this user.  Check the site log for details.", "danger");
				}
				else {
					$systemMessages->addMessage("Pending AFSC association(s) approved successfully.", "success");
				}
			}
			else{
				$error = true;
				$systemMessages->addMessage("You did not provide any AFSC's to approve.", "warning");
			}
			break;
		case "removePendingAssociation":
			$error = false;

			if(isset($_POST['afscUUID'])) {
				foreach ($_POST['afscUUID'] as $afscUUID) {
					if (!$associationManager->deletePendingAFSCAssociation($userUUID, $afscUUID)) {
						$error = true;
					}
				}

				if ($error) {
					$systemMessages->addMessage("There were errors while removing pending AFSC association(s) for this user.  Check the site log for details.", "danger");
				}
				else {
					$systemMessages->addMessage("Pending AFSC association(s) removed successfully.", "success");
				}
			}
			else{
				$error = true;
				$systemMessages->addMessage("You did not provide any AFSC's to remove.", "warning");
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
						<li><a href="/admin/users/<?php echo $userUUID; ?>"><i class="icon-inline icon-20 ic-arrow-left"></i>Return to user manager</a></li>
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
				<?php foreach($userPendingAFSCList as $pendingAFSCUUID => $pendingAFSCName): ?>
				<li>&raquo; <?php echo $pendingAFSCName; ?></li>
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
				$afscList = $afscManager->listAFSC(false);
				if(!empty($userAFSCList)) {
                    foreach ($userAFSCList as $userAFSC) {
                        if (isset($afscList[$userAFSC])) {
                            unset($afscList[$userAFSC]);
                        }
                    }
                }
				
				if($afscList): ?>
				<select class="input_full" name="afscUUID[]" size="8" MULTIPLE>
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
				<select class="input_full" name="afscUUID[]" size="8" MULTIPLE>
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
				<select class="input_full" name="afscUUID[]" size="8" MULTIPLE>
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
		<div class="4u">
			<section>
				<header>
					<h2>Remove Pending Associations</h2>
				</header>
			</section>
			<form action="/admin/users/<?php echo $userUUID; ?>/associations/afsc" method="POST">
				<input type="hidden" name="formAction" value="removePendingAssociation">
				<?php if($userPendingAFSCList): ?>
				<select class="input_full" name="afscUUID[]" size="8" MULTIPLE>
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