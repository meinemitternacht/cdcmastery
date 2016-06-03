<?php
$action = isset($_SESSION['vars'][0]) ? $_SESSION['vars'][0] : false;
$officeSymbolUUID = isset($_SESSION['vars'][1]) ? $_SESSION['vars'][1] : false;

if($action){
	if($action == "add"): 
		if(isset($_POST['officeSymbolName'])):
			if($officeSymbol->addOfficeSymbol($_POST['officeSymbolName'])){
				$sysMsg->addMessage("Office Symbol added successfully.","success");
				$cdcMastery->redirect("/admin/office-symbols");
			}
			else{
				$sysMsg->addMessage("There was a problem adding the Office Symbol.","danger");
				$cdcMastery->redirect("/admin/office-symbols/add");
			}
		else: ?>
		<div class="container">
			<div class="row">
				<div class="8u">
					<section>
						<header>
							<h2>Add Office Symbol</h2>
						</header>
						<form action="/admin/office-symbols/add" method="POST">
							<label for="officeSymbolName">Office Symbol</label>
							<br>
							<input type="text" name="officeSymbolName">
							<input type="submit" value="Add Office Symbol">
						</form>
					</section>
				</div>
			</div>
		</div>
		<?php endif; ?>
	<?php
	elseif($action == "edit"):
		if(isset($_POST['officeSymbolName'])):
			if($officeSymbol->editOfficeSymbol($officeSymbolUUID,$_POST['officeSymbolName'])){
				$sysMsg->addMessage("Office Symbol edited successfully.","success");
				$cdcMastery->redirect("/admin/office-symbols");
			}
			else{
				$sysMsg->addMessage("There was a problem editing the Office Symbol.","danger");
				$cdcMastery->redirect("/admin/office-symbols/edit/" . $officeSymbolUUID);
			}
		else: 
			if($officeSymbol->loadOfficeSymbol($officeSymbolUUID)): ?>
				<div class="container">
					<div class="row">
						<div class="8u">
							<section>
								<header>
									<h2>Edit Office Symbol</h2>
								</header>
								<form action="/admin/office-symbols/edit/<?php echo $officeSymbolUUID; ?>" method="POST">
									<label for="officeSymbolName">Office Symbol</label>
									<br>
									<input type="text" name="officeSymbolName" value="<?php echo $officeSymbol->getOfficeSymbol(); ?>">
									<input type="submit" value="Edit Office Symbol">
								</form>
							</section>
						</div>
					</div>
				</div>
			<?php
			else:
				$sysMsg->addMessage("That Office Symbol does not exist.","danger");
				$cdcMastery->redirect("/admin/office-symbols");
			endif;
		endif; ?>
	<?php
	elseif($action == "delete"):
		if($officeSymbol->deleteOfficeSymbol($officeSymbolUUID)){
			$sysMsg->addMessage("Office Symbol deleted successfully.","success");
			$cdcMastery->redirect("/admin/office-symbols");
		}
		else{
			$sysMsg->addMessage("There was a problem deleting that Office Symbol.","danger");
			$cdcMastery->redirect("/admin/office-symbols");
		}
	endif;
}
else{
	/*
	 * Show list of office symbols with options
	 */
	$officeSymbolList = $officeSymbol->listOfficeSymbols();
	?>
	<div class="container">
		<div class="row">
			<div class="3u">
				<section>
					<div class="sub-menu">
						<ul>
							<li><a href="/admin"><i class="icon-inline icon-20 ic-arrow-left"></i>Return to Admin Panel</a></li>
							<li><a href="/admin/office-symbols/add"><i class="icon-inline icon-20 ic-plus"></i>Add Office Symbol</a>
						</ul>
					</div>
				</section>
			</div>
			<div class="4u">
				<section>
					<header>
						<h2>Office Symbol Management</h2>
					</header>
					<form action="/admin/office-symbols/add" method="POST">
					<table>
						<tr>
							<th style="width:80%">Office Symbol</th>
							<th style="width:20%">Actions</th>
						</tr>
						<tr>
							<td><input type="text" name="officeSymbolName" id="officeSymbolName" autofocus></td>
							<td><input type="submit" value="Add"></td>
						</tr>
						<?php foreach($officeSymbolList as $osUUID => $officeSymbolText): ?>
						<tr>
							<td><?php echo $officeSymbolText; ?></td>
							<td>
								<a href="/admin/office-symbols/edit/<?php echo $osUUID; ?>" title="Edit"><i class="icon-inline icon-20 ic-pencil"></i></a>
								<a href="/admin/office-symbols/delete/<?php echo $osUUID; ?>" title="Delete"><i class="icon-inline icon-20 ic-delete"></i></a>
							</td>
						</tr>
						<?php endforeach; ?>
					</table>
					</form>
				</section>
			</div>
		</div>
	</div>
	<?php
}