<?php
if(!isset($_SESSION['auth'])):
	if(!empty($_POST)){
		$userUUID = $user->userLoginName($_POST['username']);
		
		if(!$userUUID){
            $sysMsg->addMessage($user->error);
		}
		else{
			$a = new auth($userUUID,$log,$db,$roles,$emailQueue);
	
			if(!$a->login($_POST['password'])){
                $sysMsg->addMessage($a->getError());
                $cdcMastery->redirect("/auth/login");
			}
			else{
				$cdcMastery->redirect("/");
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
                            <label for="username">Username</label>
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
                        <em>It's easy, fast, and <strong>free</strong>!</em>
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