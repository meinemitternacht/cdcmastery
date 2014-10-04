<?php
if(!isset($_SESSION['auth'])):
	if(!empty($_POST)){
		$userUUID = $user->userLoginName($_POST['username']);
		
		if(!$userUUID){
			echo $user->error;
		}
		else{
			$a = new auth($userUUID,$log,$db,$roles,$emailQueue);
	
			if(!$a->login($_POST['password'])){
				echo $a->getError();
			}
			else{
				$cdcMastery->redirect("/");
				exit();
			}
		}
	}
	?>
	<div id="content" class="4u skel-cell-important">
		<section>
			<header>
				<h3>Login</h3>
			</header>
			<form class="light-form" action="/auth/login" method="POST">
			<div>
				<label class="pretty bold" for="username">Username</label>
				<input class="pretty" type="text" name="username" />
			</div>
			<div>
				<label class="pretty bold" for="password">Password</label>
				<input class="pretty" type="password" name="password" />
			</div>
			<div class="submit">
				<input style="margin: 0 auto;" class="center" type="submit" value="Log In" />
			</div>
		</form>
		</section>
	</div>
<?php 
else:
	$_SESSION['error'][] = "You are already logged in.";
	$cdcMastery->redirect("/");
endif; ?>