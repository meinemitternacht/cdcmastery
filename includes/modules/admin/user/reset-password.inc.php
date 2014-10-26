<?php
if(isset($_POST['confirmPasswordReset'])){
	if($userUUID){
		$pwReset = new passwordReset($db, $log, $emailQueue);
		
		if($pwReset->sendPasswordReset($userUUID)){
			$_SESSION['messages'][] = "A password reset link has been sent to " . $objUser->getFullName();
			$cdcMastery->redirect("/admin/users/" . $userUUID);
		}
		else{
			$_SESSION['messages'][] = "Sorry, we could not reset the password for " . $objUser->getFullName();
			$cdcMastery->redirect("/admin/users/" . $userUUID);
		}
	}
	else{
		$_SESSION['messages'][] = "No User UUID was provided.";
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
			</section>
			<div class="sub-menu">
				<div class="menu-heading">
					Reset Password
				</div>
				<ul>
					<li><a href="/admin/users/<?php echo $userUUID; ?>"><i class="fa fa-caret-square-o-left fa-fw"></i>Return to user manager</a></li>
				</ul>
			</div>
		</div>
		<div class="8u">
			<section>
				<header>
					<h2>Confirm Password Reset</h2>
				</header>
				<br>
				<form action="/admin/users/<?php echo $userUUID; ?>/reset-password" method="POST">
					<input type="hidden" name="confirmPasswordReset" value="1">
					If you wish to reset the password for <?php echo $objUser->getFullName(); ?>, please press continue.
					Otherwise, <a href="/admin/users/<?php echo $userUUID; ?>">return to the user menu</a>.
					<br>
					<input type="submit" value="Continue">
				</form>
			</section>
		</div>
	</div>
</div>
<?php
}