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
	<div class="container">
		<div class="row">
			<div id="content" class="4u skel-cell-important">
				<section>
					<header>
						<h3>Login</h3>
					</header>
					<form action="/auth/login" method="POST">
					<div>
						<label for="username">Username</label>
						<br>
						<input type="text" id="username" name="username" />
					</div>
					<div>
						<label for="password">Password</label>
						<br>
						<input type="password" id="password" name="password" />
					</div>
					<br>
						<input type="submit" value="Log In" />
				</form>
				</section>
			</div>
		</div>
	</div>
<?php 
else:
	$_SESSION['error'][] = "You are already logged in.";
	$cdcMastery->redirect("/");
endif; ?>