<?php
if(!isset($_SESSION['auth'])):
	if(!empty($_POST)){
        if(!isset($_POST['username']) || empty($_POST['username'])){
            $log->setAction("ERROR_LOGIN_EMPTY_USER");
            $log->setDetail("Provided Username",$_POST['username']);
            $log->saveEntry();

            $sysMsg->addMessage("Your username cannot be blank.");
            $cdcMastery->redirect("/auth/login");
        }

        if(!isset($_POST['password']) || empty($_POST['password'])){
            $log->setAction("ERROR_LOGIN_EMPTY_PASSWORD");
            $log->setDetail("Provided Username",$_POST['username']);
            $log->saveEntry();

            $sysMsg->addMessage("Your password cannot be blank.");
            $cdcMastery->redirect("/auth/login");
        }

		$userUUID = $user->userLoginName($_POST['username']);
		
		if(!$userUUID){
            $sysMsg->addMessage($user->error);
            $log->setAction("ERROR_LOGIN_UNKNOWN_USER");
            $log->setDetail("Provided Username",$_POST['username']);
            $log->saveEntry();
		}
		else{
			$a = new auth($userUUID,$log,$db,$roles,$emailQueue);
	
			if(!$a->login($_POST['password'])){
                $sysMsg->addMessage($a->getError());
                $cdcMastery->redirect("/auth/login");
			}
			else{
                $session->regenerate_id();
                if(isset($_SESSION['nextPage']) && !empty($_SESSION['nextPage'])){
                    $cdcMastery->redirect($_SESSION['nextPage']);
                }
                else {
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
                            <a href="/auth/reset">Forgot Password</a>
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
    $sysMsg->addMessage("You are already logged in.");
	$cdcMastery->redirect("/");
endif; ?>