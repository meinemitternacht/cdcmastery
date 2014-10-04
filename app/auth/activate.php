<?php
$userActivate = new userActivation($db, $log, $emailQueue);

if(isset($_SESSION['vars'][0]))
	$activationToken = $_SESSION['vars'][0];

if(!empty($_POST) && isset($_POST['userEmail'])){
	$userUUID = $userActivate->getUUIDByEmail($_POST['userEmail']);
	$action = $_POST['action'];
	
	if($userUUID){
		if($userActivate->verifyUser($userUUID)){
			if($action == "resend"){
				if($userActivate->queueActivation($userUUID)){
					$messages[] = "An activation link was sent to your e-mail address.";
				}
				else{
					$messages[] = "We were unable to send an activation link to your e-mail address.  Contact CDCMastery Support (support@cdcmastery.com) for further assistance.";
				}
			}
		}
		else{
			$messages[] = "That user does not exist.";
		}
	}
	else{
		$messages[] = "Sorry, we could not find your e-mail in the database.  Make sure it is typed correctly and try again.";
	}
}

if(isset($activationToken)):
	if($userActivate->verifyActivationToken($activationToken)):
		if($userActivate->activateUser($activationToken)):
			$_SESSION['messages'][] = "Thank you for activating your account.  Please login using the form below.";
			$cdcMastery->redirect("/auth/login");
		else:
			$messages[] = "Sorry, we could not process the activation for your account.  Contact CDCMastery Support (support@cdcmastery.com) for further assistance.";
		endif;
	else: ?>
	<div class="container">
		<?php if(isset($messages)): ?>
		<div class="systemMessages">
			<ul>
			<?php foreach($messages as $message): ?>
				<li><?php echo $message; ?></li>
			<?php endforeach; ?>
			</ul>
		</div>
		<?php endif; ?>
		<div class="row">
			<div class="12u">
				<section>
					<header>
						<h2>Activate your account</h2>
					</header>
				</section>
				Sorry, that activation token is invalid or has expired.  To send another activation link to your e-mail address, enter the e-mail you registered with below:<br>
				<br>
				<form action="/auth/activate" method="POST">
					<input type="hidden" name="action" value="resend">
					<input type="text" name="userEmail" size="30"><br>
					<br>
					<input type="submit" value="Send Activation Link">
				</form>
			</div>
		</div>
	</div>
	<?php endif;
else: ?>
	<div class="container">
		<?php if(isset($messages)): ?>
		<div class="systemMessages">
			<ul>
			<?php foreach($messages as $message): ?>
				<li><?php echo $message; ?></li>
			<?php endforeach; ?>
			</ul>
		</div>
		<?php endif; ?>
		<div class="row">
			<div class="12u">
				<section>
					<header>
						<h2>Activate your account</h2>
					</header>
				</section>
				To send another activation link to your e-mail address, enter the e-mail you registered with below:<br>
				<br>
				<form action="/auth/activate" method="POST">
					<input type="hidden" name="action" value="resend">
					<input type="text" name="userEmail" size="30"><br>
					<br>
					<input type="submit" value="Send Activation Link">
				</form>
			</div>
		</div>
	</div>
<?php endif;?>