<?php
if(!empty($_POST) && isset($_POST['formAction'])){
	switch($_POST['formAction']){
		case "approvePendingAssociation":
			$error = false;

			$pendingAssociationList = $assoc->listPendingAFSCAssociations();

			foreach($_POST['assocUUIDList'] as $assocUUID){
				if(!$assoc->approvePendingAFSCAssociation($pendingAssociationList[$assocUUID]['userUUID'], $pendingAssociationList[$assocUUID]['afscUUID'])){
					$error = true;
				}
			}

			if($error){
				$sysMsg->addMessage("There were errors while approving pending AFSC association(s) for this user.  Check the site log for details.","danger");
			}
			else{
				$sysMsg->addMessage("Pending AFSC association(s) approved successfully.","success");
			}
			break;
	}
}

$pendingAssociationList = $assoc->listPendingAFSCAssociations();

if($pendingAssociationList): ?>
	<script type="text/javascript">
		$(document).ready(function() {
			$('#selectAll').click(function(event) {  //on click
				if(this.checked) { // check select status
					$('.selectAssociation').each(function() { //loop through each checkbox
						this.checked = true;  //select all checkboxes with class "checkbox1"
					});
				}else{
					$('.selectAssociation').each(function() { //loop through each checkbox
						this.checked = false; //deselect all checkboxes with class "checkbox1"
					});
				}
			});

		});
	</script>
	<div class="container">
		<div class="row">
			<div class="5u">
				<section>
					<header>
						<h2>Approve Pending AFSC Associations</h2>
					</header>
				</section>
			</div>
		</div>
		<div class="row">
			<div class="6u">
				<section>
					<p>Select the AFSC's you wish to approve below.</p>
					<form action="/admin/afsc-pending" method="POST">
						<input type="hidden" name="formAction" value="approvePendingAssociation">
						<table>
							<tr>
								<th><input type="checkbox" name="selectAll" id="selectAll"></th>
								<th>User</th>
								<th>AFSC</th>
							</tr>
							<?php foreach($pendingAssociationList as $assocUUID => $assocData): ?>
							<tr>
								<td><input type="checkbox" class="selectAssociation" name="assocUUIDList[]" value="<?php echo $assocUUID; ?>"></td>
								<td><a href="/admin/profile/<?php echo $assocData['userUUID']; ?>"><?php echo $user->getUserNameByUUID($assocData['userUUID']); ?></a></td>
								<td><?php echo $assocData['afscName']; ?></td>
							</tr>
							<?php endforeach; ?>
						</table>
						<div class="clearfix">&nbsp;</div>
						<input type="submit" value="Approve Associations">
					</form>
				</section>
			</div>
		</div>
	</div>
<?php else:
	$sysMsg->addMessage("There are no pending AFSC Associations.","info");
	$cdcMastery->redirect("/admin");
endif;