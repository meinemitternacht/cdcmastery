<?php
if(!empty($_POST) && isset($_POST['formAction'])){
	switch($_POST['formAction']){
		case "addAssociation":
			$error = false;
			
			foreach($_POST['afscUUID'] as $afscUUID){
				if($afsc->loadAFSC($afscUUID)) {
					if ($afsc->getAFSCFOUO()) {
						if (!$assoc->addAFSCAssociation($_SESSION['userUUID'], $afscUUID, false)) {
							$error = true;
						}
					} else {
						if (!$assoc->addAFSCAssociation($_SESSION['userUUID'], $afscUUID)) {
							$error = true;
						}
					}
				}
				else{
					$error = true;
				}
			}
			
			if($error){
                $sysMsg->addMessage("There were errors while adding AFSC association(s) for this user.  Check the site log for details.");
			}
			else{
				$sysMsg->addMessage("AFSC association(s) added successfully.");
			}
		break;
		case "removeAssociation":
			$error = false;
			
			foreach($_POST['afscUUID'] as $afscUUID){
				if(!$assoc->deleteAFSCAssociation($_SESSION['userUUID'], $afscUUID)){
					$error = true;
				}
			}
			
			if($error){
				$sysMsg->addMessage("There were errors while removing AFSC association(s) for this user.  Check the site log for details.");
			}
			else{
				$sysMsg->addMessage("AFSC association(s) removed successfully.");
			}
		break;
		case "removePendingAssociation":
			$error = false;
			
			foreach($_POST['afscUUID'] as $afscUUID){
				if(!$assoc->deletePendingAFSCAssociation($_SESSION['userUUID'], $afscUUID)){
					$error = true;
				}
			}
			
			if($error){
				$sysMsg->addMessage("There were errors while removing pending AFSC association(s) for this user.  Check the site log for details.");
			}
			else{
				$sysMsg->addMessage("Pending AFSC association(s) removed successfully.");
			}
		break;
	}
}

$userStatistics->setUserUUID($_SESSION['userUUID']);
$userAFSCList = $userStatistics->getAFSCAssociations();
$userPendingAFSCList = $userStatistics->getPendingAFSCAssociations();
?>
<div class="container">
	<div class="row">
		<div class="4u">
			<section>
				<header>
					<h2><em><?php echo $user->getFullName(); ?></em></h2>
				</header>
				<div class="sub-menu">
					<div class="menu-heading">
						AFSC Associations
					</div>
					<ul>
						<li><a href="/user/profile"><i class="icon-inline icon-20 ic-arrow-left"></i>Return to profile</a></li>
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
			<em>You have no AFSC associations.</em>
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
			<em>You have no pending AFSC associations.</em>
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
			<form action="/user/afsc-associations" method="POST">
				<input type="hidden" name="formAction" value="addAssociation">
				<?php
				$afscList = $afsc->listAFSC(false);
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
				<div class="clearfix">&nbsp;</div>
				<input type="submit" value="Add">
				<p><em>An asterisk (*) denotes a FOUO AFSC.  If you add one of these, you will be placed into a queue for approval by an administrator or training manager.  This process is not immediate, and may take up to 24 hours.</em></p>
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
			<form action="/user/afsc-associations" method="POST">
				<input type="hidden" name="formAction" value="removeAssociation">
				<?php if($userAFSCList): ?>
				<select class="input_full" name="afscUUID[]" size="8" MULTIPLE>
					<?php foreach($userAFSCList as $listUUID => $listAFSCName): ?>
					<option value="<?php echo $listUUID; ?>"><?php echo $listAFSCName; ?></option>
					<?php endforeach; ?>
				</select>
				<div class="clearfix">&nbsp;</div>
				<input type="submit" value="Remove">
				<p><em>Note: If you accidentally remove a FOUO AFSC from this list, re-adding it to your account will require administrator or training manager approval, which may take up to 24 hours.</em></p>
				<?php else: ?>
				<em>You have no AFSC associations.</em>
				<?php endif; ?>
			</form>
		</div>
		<div class="4u">
			<section>
				<header>
					<h2>Remove Pending Associations</h2>
				</header>
			</section>
			<form action="/user/afsc-associations" method="POST">
				<input type="hidden" name="formAction" value="removePendingAssociation">
				<?php if($userPendingAFSCList): ?>
				<select class="input_full" name="afscUUID[]" size="8" MULTIPLE>
					<?php foreach($userPendingAFSCList as $listUUID => $listAFSCName): ?>
					<option value="<?php echo $listUUID; ?>"><?php echo $listAFSCName; ?></option>
					<?php endforeach; ?>
				</select>
				<div class="clearfix">&nbsp;</div>
				<input type="submit" value="Remove">
				<?php else: ?>
				<em>You have no pending AFSC associations.</em>
				<?php endif; ?>
			</form>
		</div>
	</div>
</div>