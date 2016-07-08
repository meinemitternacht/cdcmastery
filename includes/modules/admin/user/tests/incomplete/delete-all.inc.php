<?php
$testManager = new CDCMastery\TestManager($db, $systemLog, $afscManager);

if(isset($_POST['confirmIncompleteTestDeleteAll'])){
	if($testManager->deleteIncompleteTest(true,false,$userUUID)){
		$userStatistics->setUserUUID($userUUID);
		$userStatistics->deleteUserStatsCacheVal("getIncompleteTests");
        $systemMessages->addMessage("Incomplete tests deleted successfully.", "success");
		$cdcMastery->redirect("/admin/users/" . $userUUID);
	}
	else{
        $systemMessages->addMessage("We could not delete the incomplete tests taken by this user, please contact the support help desk.", "danger");
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
						<li><a href="/admin/users/<?php echo $userUUID; ?>"><i class="icon-inline icon-20 ic-arrow-left"></i>Return to user manager</a></li>
					</ul>
				</div>
			</section>
		</div>
		<div class="6u">
			<section>
				<header>
					<h2>Confirm Deletion</h2>
				</header>
				<form action="/admin/users/<?php echo $userUUID; ?>/tests/incomplete/delete-all" method="POST">
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