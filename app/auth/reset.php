<?php
$pwReset = new passwordReset($db, $log, $emailQueue);

if(isset($_SESSION['vars'][0]) && isset($_SESSION['vars'][1])){
	$passwordToken = $_SESSION['vars'][0];
	$action = $_SESSION['vars'][1];
	$userPassword = $_POST['newPassword'];
	$userPasswordConfirm = $_POST['confirmNewPassword'];

    $passwordComplexityCheck = $pwReset->checkPasswordComplexity($userPassword);

    if(is_array($passwordComplexityCheck)){
        foreach($passwordComplexityCheck as $passwordComplexityCheckError){
            $sysMsg->addMessage($passwordComplexityCheckError);
        }

        $cdcMastery->redirect("/auth/reset/".$_SESSION['vars'][0]);
    }
	elseif($userPassword != $userPasswordConfirm){
		$sysMsg->addMessage("Your passwords do not match.");
	}
	else{
		if($pwReset->verifyPasswordResetToken($passwordToken)){
			$userUUID = $pwReset->getPasswordResetUser($passwordToken);
			
			if($pwReset->verifyUser($userUUID)){
				$pwReset->loadUser($userUUID);
				$pwReset->setUserPassword($userPassword);
				$pwReset->setUserLegacyPassword(false);
				if($pwReset->saveUser()){
					$log->setAction("USER_PASSWORD_RESET_COMPLETE");
					$log->saveEntry();
                    $pwReset->deletePasswordResetToken($passwordToken);

                    $sysMsg->addMessage("Your password has been reset. You may now log in with your new password.");
					$cdcMastery->redirect("/auth/login");
				}
				else{
					$sysMsg->addMessage("We could not update your password.  Contact CDCMastery Support (support@cdcmastery.com) for further assistance.");
				}
			}
			else{
				$sysMsg->addMessage("That user does not exist.");
			}
		}
		else{
			$sysMsg->addMessage("That password reset token is invalid.");
		}
	}
}

if(!empty($_POST) && isset($_POST['userEmail'])){
	$userEmail = $_POST['userEmail'];
	
	$userUUID = $pwReset->getUUIDByEmail($userEmail);
	
	if($userUUID){
        $auth = new auth($userUUID,$log,$db,$roles,$emailQueue);

        if($auth->getActivationStatus()) {
            if ($pwReset->sendPasswordReset($userUUID)) {
                $sysMsg->addMessage("A password reset link has been sent to the e-mail address provided.");
            } else {
                $sysMsg->addMessage("Sorry, we could not send a password reset to that e-mail address.  Contact CDCMastery Support (support@cdcmastery.com) for further assistance.");
            }
        }
        else{
            $sysMsg->addMessage("Please activate your account before performing a password reset. If you did not receive an activation e-mail within one hour of registering, please send a new activation e-mail below.");
            $cdcMastery->redirect("/auth/activate");
        }
	}
	else{
		$sysMsg->addMessage("Sorry, we could not find your account.  Re-check your typed e-mail address and try again.");
	}
}
?>
<div class="container">
	<div class="row">
		<div class="12u">
			<section>
				<header>
					<h2>Reset Password</h2>
				</header>
			</section>
			<?php
			if(isset($_SESSION['vars'][0])):
				$passwordToken = $_SESSION['vars'][0];
				
				if($pwReset->verifyPasswordResetToken($passwordToken)): ?>
					Please choose a new password:<br>
					<br>
					<form action="/auth/reset/<?php echo $_SESSION['vars'][0]; ?>/password" method="POST">
						<strong>New Password</strong><br>
						<input type="password" name="newPassword" size="30"><br>
						<strong>Confirm Password</strong><br>
						<input type="password" name="confirmNewPassword" size="30"><br>
						<br>
						<input type="submit" value="Change Password">
					</form>
				<?php else:
                        $sysMsg->addMessage("That password reset token is invalid.  Please try again or contact the helpdesk for assistance by clicking the 'support' link at the top of the page.");
                        $cdcMastery->redirect("/auth/reset");
                    endif; ?>
			<?php else: ?>
			In order to reset your password, please type the e-mail address associated with your account in the text box below.  A password reset link will be sent to this address, and will expire in 24 hours.<br>
			<br>
			<form action="/auth/reset" method="POST">
				<strong>E-mail Address</strong>
				<br>
				<input name="userEmail" type="text" size="30"><br>
				<br>
				<input type="submit" value="Send Password Reset">
			</form>
			<?php endif; ?>
		</div>
	</div>
</div>