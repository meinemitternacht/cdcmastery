<?php
if(isset($_POST['confirmActivationResend'])){
	if($userUUID){
		$userActivate = new UserActivationManager($db, $systemLog, $emailQueue);
		
		if($userActivate->queueActivation($userUUID)){
			$systemMessages->addMessage("Activation code sent to " . $objUser->getFullName(), "success");
			$cdcMastery->redirect("/admin/users/" . $userUUID);
		}
		else{
            $systemMessages->addMessage("Sorry, we could not send an activation code to " . $objUser->getFullName(), "danger");
			$cdcMastery->redirect("/admin/users/" . $userUUID);
		}
	}
	else{
        $systemMessages->addMessage("No User UUID was provided.", "warning");
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
					Resend Activation
				</div>
				<ul>
					<li><a href="/admin/users/<?php echo $userUUID; ?>"><i class="icon-inline icon-20 ic-arrow-left"></i>Return to user manager</a></li>
				</ul>
			</div>
		</div>
		<div class="6u">
			<section>
				<header>
					<h2>Confirm Resend of Activation Code</h2>
				</header>
				<br>
				<form action="/admin/users/<?php echo $userUUID; ?>/resend-activation" method="POST">
					<input type="hidden" name="confirmActivationResend" value="1">
					If you wish to resend an activation code to <?php echo $objUser->getFullName(); ?>, please press continue.
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