<?php
if(!$finalChild){
    $sysMsg->addMessage("You did not specify an incomplete test to delete.");
    $cdcMastery->redirect("/admin/users/".$userUUID."/tests/incomplete");
}

$testManager = new testManager($db, $log, $afsc);

if(isset($_POST['confirmIncompleteTestDelete'])){
	if($testManager->deleteIncompleteTest(false,$finalChild)){
		$userStatistics->setUserUUID($userUUID);
		$userStatistics->deleteUserStatsCacheVal("getIncompleteTests");
        $sysMsg->addMessage("Incomplete test deleted successfully.");
		$cdcMastery->redirect("/admin/users/" . $userUUID);
	}
	else{
        $sysMsg->addMessage("We could not delete that incomplete test. Please contact the support helpdesk.");
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
						Delete Incomplete Test
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
				<form action="/admin/users/<?php echo $userUUID; ?>/tests/incomplete/delete/<?php echo $finalChild; ?>" method="POST">
					<input type="hidden" name="confirmIncompleteTestDelete" value="1">
					If you wish to delete the incomplete test, please press continue.
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