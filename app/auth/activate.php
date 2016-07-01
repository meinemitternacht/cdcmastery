<?php
$userActivate = new UserActivationManager($db, $systemLog, $emailQueue);

if(isset($_SESSION['vars'][0]))
	$activationToken = $_SESSION['vars'][0];

if(!empty($_POST) && isset($_POST['action'])){
    if($_POST['action'] == "resend") {
        $userUUID = $userActivate->getUUIDByEmail($_POST['userEmail']);
        $action = $_POST['action'];

        if ($userUUID) {
            if ($userActivate->verifyUser($userUUID)) {
                if ($action == "resend") {
                    if ($userActivate->queueActivation($userUUID)) {
                        $systemMessages->addMessage("An activation link was sent to your e-mail address.", "success");
                    } else {
                        $systemMessages->addMessage("We were unable to send an activation link to your e-mail address.  Contact CDCMastery Support (support@cdcmastery.com) for further assistance.", "danger");
                    }
                }
            } else {
                $systemMessages->addMessage("That user does not exist.", "warning");
            }
        } else {
            $systemMessages->addMessage("Sorry, we could not find your e-mail in the database.  Make sure it is typed correctly and try again.", "warning");
        }
    }
    elseif($_POST['action'] == "activate"){
        if(isset($_POST['activationCode']) && !empty($_POST['activationCode'])){
            $cdcMastery->redirect("/auth/activate/".$_POST['activationCode']);
        }
        else{
            $systemMessages->addMessage("You must provide an activation code.", "warning");
            $cdcMastery->redirect("/auth/activate");
        }
    }
}

if(isset($activationToken)):
	if($userActivate->verifyActivationToken($activationToken)):
		if($userActivate->activateUser($activationToken)):
            if(isset($_SESSION['queueActivation'])){
                unset($_SESSION['queueActivation']);
            }

			$systemMessages->addMessage("Thank you for activating your account.  Please login using the form below.", "info");
			$cdcMastery->redirect("/auth/login");
		else:
			$systemMessages->addMessage("Sorry, we could not process the activation for your account.  Contact CDCMastery Support (support@cdcmastery.com) for further assistance.", "danger");
		endif;
	else: ?>
	<div class="container">
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
		<div class="row">
			<div class="12u">
				<section>
					<header>
						<h2>Activate your account</h2>
					</header>
				</section>
                Please enter your activation code in tbe box below to activate your account.<br>
                <br>
                <form action="/auth/activate" method="POST">
                    <input type="hidden" name="action" value="activate">
                    <input type="text" name="activationCode" size="30"><br>
                    <br>
                    <input type="submit" value="Verify Code">
                </form>
                <br>
                <br>
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