<?php
$testManager = new testManager($db, $log, $afsc);

if(isset($_POST['confirmIncompleteTestDeleteAll'])){
	if($testManager->deleteIncompleteTest(true,false,$userUUID)){
		$_SESSION['messages'][] = "Incomplete tests deleted successfully.";
		$cdcMastery->redirect("/admin/users/" . $userUUID);
	}
	else{
		$_SESSION['messages'][] = "We could not delete the incomplete tests taken by this user, please <a href=\"http://helpdesk.cdcmastery.com\">submit a ticket</a>.";
		$cdcMastery->redirect("/admin/users/" . $userUUID);
	}
}
else{ ?>
<div class="container">
	<div class="row">
		<div class="4u">
			<section>
				<header>
					<h2><em><?php echo $objUser->getFullName(); ?></em></h2>
				</header>
				<div class="sub-menu">
					<div class="menu-heading">
						Delete All Incomplete Tests
					</div>
					<ul>
						<li><a href="/admin/users/<?php echo $userUUID; ?>"><i class="fa fa-caret-square-o-left fa-fw"></i>Return to user manager</a></li>
					</ul>
				</div>
			</section>
		</div>
		<div class="6u">
			<section>
				<header>
					<h2>Confirm Deletion</h2>
				</header>
				<form action="/admin/users/<?php echo $userUUID; ?>/tests/incomplete/delete" method="POST">
					<input type="hidden" name="confirmIncompleteTestDeleteAll" value="1">
					If you wish to delete all incomplete tests taken by this user, please press continue.
					Otherwise, <a href="/admin/users/<?php echo $userUUID; ?>">return to the user manager</a>.
					<br>
					<br>
					<input type="submit" value="Continue">
				</form>
			</section>
		</div>
	</div>
</div>
<?php
}