<?php
if(isset($_SESSION['queueActivation']))
    $systemMessages->addMessage("Your account has not yet been activated. Please activate your account by following the link in the e-mail sent to the address you registered with.", "info");

if(!isset($_SESSION['auth'])):
	if(!empty($_POST)){
        if(!isset($_POST['username']) || empty($_POST['username'])){
            $systemLog->setAction("ERROR_LOGIN_EMPTY_USER");
            $systemLog->setDetail("Provided Username", $_POST['username']);
            $systemLog->setDetail("Remote Port", $_SERVER['REMOTE_PORT']);
            $systemLog->saveEntry();

            $systemMessages->addMessage("Your username cannot be blank.", "warning");
            $cdcMastery->redirect("/auth/login");
        }

        if(!isset($_POST['password']) || empty($_POST['password'])){
            $systemLog->setAction("ERROR_LOGIN_EMPTY_PASSWORD");
            $systemLog->setDetail("Provided Username", $_POST['username']);
            $systemLog->setDetail("Remote Port", $_SERVER['REMOTE_PORT']);
            $systemLog->saveEntry();

            $systemMessages->addMessage("Your password cannot be blank.", "warning");
            $cdcMastery->redirect("/auth/login");
        }

		$userUUID = $userManager->userLoginName($_POST['username']);
		
		if(!$userUUID){
            $systemMessages->addMessage($userManager->error);
            $systemLog->setAction("ERROR_LOGIN_UNKNOWN_USER");
            $systemLog->setDetail("Provided Username", $_POST['username']);
            $systemLog->setDetail("Remote Port", $_SERVER['REMOTE_PORT']);
            $systemLog->saveEntry();
		}
		else{
			$a = new AuthenticationManager($userUUID, $systemLog, $db, $roleManager, $emailQueue);
	
			if(!$a->login($_POST['password'])){
                $systemMessages->addMessage($a->getError());
                $cdcMastery->redirect("/auth/login");
			}
			else{
                $zebraSession->regenerate_id();

                $userManager->loadUser($userUUID);

                if(isset($_SESSION['nextPage']) && !empty($_SESSION['nextPage'])){
                    $nextPage = $_SESSION['nextPage'];
                    unset($_SESSION['nextPage']);
                    $cdcMastery->redirect($nextPage);
                }
                elseif(preg_match("/\.mil/", $userManager->getUserEmail())){
                    if(!$cdcMastery->checkEmailAddress($userManager->getUserEmail())){
                        $cdcMastery->redirect("/user/update-email");
                    }
                    else{
                        $cdcMastery->redirect("/");
                    }
                }
                else{
                    $cdcMastery->redirect("/");
                }
			}
		}
	}
	?>
	<div class="container">
		<div class="row">
            <div class="3u">
                <section>
                    <header>
                        <h2>Login</h2>
                    </header>
                    <form id="loginForm" action="/auth/login" method="POST">
                        <div>
                            <label for="username">Username or E-mail</label>
                            <br>
                            <input type="text" id="username" name="username" class="input_full" />
                        </div>
                        <div>
                            <label for="password">Password</label>
                            <br>
                            <input type="password" id="password" name="password" class="input_full" />
                            <br>
                        </div>
                        <div>
                            <br>
                            <input type="submit" value="Log in">
                            <br>
                            <br>
                            <a href="/auth/register">Register</a> &mdash; <a href="/auth/reset">Forgot Password?</a>
                        </div>
                    </form>
                </section>
            </div>
            <div class="3u">
                <section>
                    <header>
                        <h2>Register</h2>
                    </header>
                    <p>
                        Don't have an account with us?  Click the button below to create one!
                        <br>
                        <br>
                        <em>It's easy, fast, and best of all, <strong>free</strong>!</em>
                        <br>
                    </p>
                    <div class="sub-menu">
                        <ul>
                            <li>
                                <a href="/auth/register"><i class="icon-inline icon-20 ic-arrow-right"></i>Create Account</a>
                            </li>
                        </ul>
                    </div>
                </section>
            </div>
		</div>
	</div>
<?php 
else:
    $systemMessages->addMessage("You are already logged in.", "info");
	$cdcMastery->redirect("/");
endif; ?>