<?php
$action = isset($_SESSION['vars'][0]) ? $_SESSION['vars'][0] : false;
$baseUUID = isset($_SESSION['vars'][1]) ? $_SESSION['vars'][1] : false;

if($action){
	if($action == "add"): 
		if(isset($_POST['baseName'])):
			if($baseManager->addBase($_POST['baseName'])){
				$systemMessages->addMessage("Base added successfully.", "success");
				$cdcMastery->redirect("/admin/bases");
			}
			else{
				$systemMessages->addMessage("There was a problem adding the Base.", "danger");
				$cdcMastery->redirect("/admin/bases/add");
			}
		else: ?>
		<div class="container">
			<div class="row">
				<div class="8u">
					<section>
						<header>
							<h2>Add Base</h2>
						</header>
						<form action="/admin/bases/add" method="POST">
							<label for="baseName">Base Name</label>
							<br>
							<input type="text" name="baseName">
							<input type="submit" value="Add Base">
						</form>
					</section>
				</div>
			</div>
		</div>
		<?php endif; ?>
	<?php
	elseif($action == "edit"):
		if(isset($_POST['baseName'])):
			if($baseManager->editBase($baseUUID, $_POST['baseName'])){
				$systemMessages->addMessage("Base edited successfully.", "success");
				$cdcMastery->redirect("/admin/bases");
			}
			else{
				$systemMessages->addMessage("There was a problem editing that base.", "danger");
				$cdcMastery->redirect("/admin/bases/edit/" . $baseUUID);
			}
		else: 
			if($baseManager->loadBase($baseUUID)): ?>
				<div class="container">
					<div class="row">
						<div class="8u">
							<section>
								<header>
									<h2>Edit Base</h2>
								</header>
								<form action="/admin/bases/edit/<?php echo $baseUUID; ?>" method="POST">
									<label for="baseName">Base Name</label>
									<br>
									<input type="text" name="baseName" value="<?php echo $baseManager->getBaseName(); ?>">
									<input type="submit" value="Edit Base">
								</form>
							</section>
						</div>
					</div>
				</div>
			<?php
			else:
				$systemMessages->addMessage("That base does not exist.", "danger");
				$cdcMastery->redirect("/admin/bases");
			endif;
		endif; ?>
	<?php
	elseif($action == "delete"):
		if($baseManager->deleteBase($baseUUID)){
			$systemMessages->addMessage("Base deleted successfully.", "success");
			$cdcMastery->redirect("/admin/bases");
		}
		else{
			$systemMessages->addMessage("There was a problem deleting that base.", "danger");
			$cdcMastery->redirect("/admin/bases");
		}
	endif;
}
else{
	/*
	 * Show list of office symbols with options
	 */
	$baseList = $baseManager->listBases();
	?>
	<div class="container">
		<div class="row">
			<div class="3u">
				<section>
					<div class="sub-menu">
						<ul>
							<li><a href="/admin"><i class="icon-inline icon-20 ic-arrow-left"></i>Return to Admin Panel</a></li>
							<li><a href="/admin/bases/add"><i class="icon-inline icon-20 ic-plus"></i>Add Base</a>
						</ul>
					</div>
				</section>
			</div>
			<div class="4u">
				<section>
					<header>
						<h2>Base Management</h2>
					</header>
					<form action="/admin/bases/add" method="POST">
					<table>
						<tr>
							<th style="width:80%">Base Name</th>
							<th style="width:20%">Actions</th>
						</tr>
						<tr>
							<td><input type="text" name="baseName"></td>
							<td><input type="submit" value="Add"></td>
						</tr>
						<?php foreach($baseList as $baseUUID => $baseName): ?>
						<tr>
							<td><?php echo $baseName; ?></td>
							<td>
								<a href="/admin/bases/edit/<?php echo $baseUUID; ?>" title="Edit"><i class="icon-inline icon-20 ic-pencil"></i></a>
								<a href="/admin/bases/delete/<?php echo $baseUUID; ?>" title="Delete"><i class="icon-inline icon-20 ic-delete"></i></a>
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